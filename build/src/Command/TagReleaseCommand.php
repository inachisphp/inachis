<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'inachis:release',
    description: 'Bumps version in composer.json and updates CHANGELOG.md',
)]
final class TagReleaseCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'type',
            InputArgument::OPTIONAL,
            'The type of version bump: patch, minor, or major',
            'patch'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = strtolower((string) $input->getArgument('type'));

        if (!in_array($type, ['major', 'minor', 'patch'], true)) {
            $io->error(sprintf('Invalid bump type "%s". Allowed values: major, minor, patch.', $type));
            return Command::FAILURE;
        }

        // 0. Pre-flight check: Run composer validate
        $io->section('Validating composer configuration');

        $process = new Process(
            ['composer', 'validate', '--strict', '--no-check-publish'],
            $this->projectDir
        );
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error("Composer validation failed:\n" . $process->getErrorOutput() . $process->getOutput());
            return Command::FAILURE;
        }

        $io->writeln('<info>✓</info> Composer configuration is valid.');

        $composerPath = $this->projectDir . '/composer.json';
        $changelogPath = $this->projectDir . '/CHANGELOG.md';

        if (!file_exists($composerPath)) {
            $io->error('composer.json not found.');
            return Command::FAILURE;
        }

        // 1. Parse current version
        $composerData = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        $currentVersion = $composerData['version'] ?? '0.0.0';

        preg_match('/^(\d+)\.(\d+)\.(\d+)/', $currentVersion, $matches);
        $major = (int) ($matches[1] ?? 0);
        $minor = (int) ($matches[2] ?? 0);
        $patch = (int) ($matches[3] ?? 0);

        match ($type) {
            'major' => [$major++, $minor = 0, $patch = 0],
            'minor' => [$minor++, $patch = 0],
            'patch' => [$patch++],
        };

        $newVersion = sprintf('%d.%d.%d', $major, $minor, $patch);
        $io->note(sprintf('Bumping version: v%s -> v%s', $currentVersion, $newVersion));

        // 2. Update composer.json
        $composerData['version'] = $newVersion;
        file_put_contents(
            $composerPath,
            json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        // 3. Update CHANGELOG.md
        if (file_exists($changelogPath)) {
            $changelog = (string) file_get_contents($changelogPath);

            // Check if there is actual content under Unreleased
            preg_match('/## \[Unreleased\]\s*\n(.*?)(?=\n## |$)/s', $changelog, $matches);
            $unreleasedContent = trim($matches[1] ?? '');

            if (empty($unreleasedContent)) {
                $io->error('CHANGELOG.md has no notes under "## [Unreleased]". Add release notes before bumping!');
                return Command::FAILURE;
            }

            $today = (new \DateTimeImmutable())->format('Y-m-d');
            $replacement = "## [Unreleased]\n\n## [{$newVersion}] - {$today}";
            $changelog = preg_replace('/## \[Unreleased\]/i', $replacement, $changelog, 1);

            file_put_contents($changelogPath, $changelog);
            $io->note(sprintf('Updated CHANGELOG.md with release [%s] - %s', $newVersion, $today));
        }

        // Output raw version line at the very end so Makefile can grep/extract it cleanly
        $io->writeln(sprintf('VERSION=%s', $newVersion));

        return Command::SUCCESS;
    }
}
