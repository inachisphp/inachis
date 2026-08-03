<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Command\System;

use Inachis\Exception\Updater\IncompatibleVersionException;
use Inachis\Exception\Updater\NoUpdateAvailableException;
use Inachis\Service\System\VersionService;
use Inachis\Updater\Planner\UpdatePlanner;
use Inachis\Updater\Provider\GithubReleaseProvider;
use Inachis\Updater\ReleaseCleaner;
use Inachis\Updater\ReleaseInstaller;
use Inachis\Updater\ReleaseLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'inachis:system:update',
    description: 'Checks for, downloads, and applies core Inachis framework updates.'
)]
final class UpdateCommand extends Command
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly GithubReleaseProvider $releaseProvider,
        private readonly UpdatePlanner $updatePlanner,
        private readonly ReleaseInstaller $releaseInstaller,
        private readonly ReleaseCleaner $releaseCleaner,
        private readonly ReleaseLocator $releaseLocator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'check-only',
                'c',
                InputOption::VALUE_NONE,
                'Check for available updates without downloading or installing them.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Bypass interactive confirmation prompt (useful for automated cron jobs).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Inachis System Updater');

        $currentVersion = $this->versionService->getVersion();
        $io->text(sprintf('Current Installed Version: <comment>v%s</comment>', $currentVersion));

        // 1. Check for release manifest
        try {
            $io->text('Checking GitHub for latest release...');
            $manifest = $this->releaseProvider->latest();
            $plan = $this->updatePlanner->plan($currentVersion, $manifest);
        } catch (NoUpdateAvailableException $e) {
            $io->success($e->getMessage());
            return Command::SUCCESS;
        } catch (IncompatibleVersionException $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (Throwable $e) {
            $io->error(sprintf('Failed checking for updates: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->section('Update Details');
        $io->table(
            ['Property', 'Value'],
            [
                ['Target Version', 'v' . $plan->targetVersion],
                ['Package Archive', $plan->package],
                ['Migrations Required', $plan->requiresMigration ? 'Yes' : 'No'],
                ['Release Date', $manifest->publishedAt ? date('F j, Y H:i', strtotime($manifest->publishedAt)) : 'N/A'],
            ]
        );

        // If user only wanted to check, stop here
        if ($input->getOption('check-only')) {
            $io->info(sprintf('New release v%s is available! Run without --check-only to install.', $plan->targetVersion));
            return Command::SUCCESS;
        }

        // 2. Interactive prompt (skipped if --force is passed)
        if (!$input->getOption('force')) {
            $confirmed = $io->confirm(
                sprintf('Are you sure you want to update from v%s to v%s?', $currentVersion, $plan->targetVersion),
                true
            );

            if (!$confirmed) {
                $io->warning('Update cancelled by user.');
                return Command::SUCCESS;
            }
        }

        // 3. Run installation
        try {
            $io->section('Executing Update Process');

            // Download
            $io->text('1/4 Downloading release package...');
            $tempArchive = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $plan->package;
            $this->releaseProvider->download($manifest, $tempArchive);

            // Shared paths
            $sharedDir = $this->releaseLocator->sharedDirectory();
            $sharedMappings = [
                '.env'                   => $sharedDir . '/.env',
                '.env.local.php'         => $sharedDir . '/.env.local.php',
                'public/imgs'            => $sharedDir . '/public/imgs',
                'var'                    => $sharedDir . '/var',
                'public/maintenance.html' => $sharedDir . '/public/maintenance.html',
            ];

            // Extract, migrate DB, swap symlink
            $io->text('2/4 Extracting archive, executing migrations, and switching symlink...');
            $this->releaseInstaller->install($manifest, $tempArchive, $sharedMappings);

            // Prune old releases
            $io->text('3/4 Cleaning up old release directories...');
            $pruned = $this->releaseCleaner->prune(keep: 3);
            if (!empty($pruned)) {
                $io->text(sprintf('   Pruned %d old release folder(s).', count($pruned)));
            }

            // Cleanup download file
            $io->text('4/4 Cleaning temporary files...');
            if (file_exists($tempArchive)) {
                unlink($tempArchive);
            }

            $io->newLine();
            $io->success(sprintf('Inachis successfully updated to v%s!', $plan->targetVersion));

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $io->newLine();
            $io->error(sprintf('Update process failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
