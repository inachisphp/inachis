<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Command;

use RuntimeException;
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
    description: 'Calculates the next version and updates CHANGELOG.md',
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

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $io = new SymfonyStyle(
            $input,
            $output
        );

        $type = strtolower(
            (string) $input->getArgument('type')
        );

        if (!in_array($type, ['major', 'minor', 'patch'], true)) {
            $io->error(sprintf(
                'Invalid bump type "%s". Allowed values: major, minor, patch.',
                $type
            ));

            return Command::FAILURE;
        }

        $io->section('Validating composer configuration');

        $process = new Process(
            [
                'composer',
                'validate',
                '--strict',
                '--no-check-publish',
            ],
            $this->projectDir
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $io->error(
                "Composer validation failed:\n"
                    . $process->getErrorOutput()
                    . $process->getOutput()
            );

            return Command::FAILURE;
        }

        $io->success(
            'Composer configuration is valid.'
        );

        $currentVersion = $this->getCurrentVersion();

        $newVersion = $this->incrementVersion(
            $currentVersion,
            $type
        );

        $io->note(sprintf(
            'Bumping version: %s -> %s',
            $currentVersion,
            $newVersion
        ));

        $this->updateChangelog(
            $newVersion,
            $io
        );

        /*
         * Makefile extracts this line.
         * Do not add output after this.
         */
        $io->writeln(
            sprintf(
                'VERSION=%s',
                $newVersion
            )
        );

        return Command::SUCCESS;
    }

    private function getCurrentVersion(): string
    {
        $process = new Process(
            [
                'git',
                'describe',
                '--tags',
                '--abbrev=0',
            ],
            $this->projectDir
        );

        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Unable to determine current version from git tags.'
            );
        }

        return ltrim(
            trim($process->getOutput()),
            'v'
        );
    }

    private function incrementVersion(
        string $version,
        string $type,
    ): string {
        if (!preg_match(
            '/^(\d+)\.(\d+)\.(\d+)$/',
            $version,
            $matches
        )) {
            throw new RuntimeException(
                sprintf(
                    'Invalid version "%s". Expected semantic version format.',
                    $version
                )
            );
        }

        $major = (int) $matches[1];
        $minor = (int) $matches[2];
        $patch = (int) $matches[3];

        switch ($type) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;

            case 'minor':
                $minor++;
                $patch = 0;
                break;

            case 'patch':
                $patch++;
                break;
        }

        return sprintf(
            '%d.%d.%d',
            $major,
            $minor,
            $patch
        );
    }

    private function updateChangelog(
        string $version,
        SymfonyStyle $io,
    ): void {
        $path = $this->projectDir
            . DIRECTORY_SEPARATOR
            . 'CHANGELOG.md';

        if (!file_exists($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(
                'Unable to read CHANGELOG.md.'
            );
        }

        preg_match(
            '/## \[Unreleased\]\s*\n(.*?)(?=\n## |$)/s',
            $contents,
            $matches
        );

        $unreleased = trim(
            $matches[1] ?? ''
        );

        if ($unreleased === '') {
            throw new RuntimeException(
                'CHANGELOG.md has no notes under [Unreleased].'
            );
        }

        $today = (new \DateTimeImmutable())
            ->format('Y-m-d');

        $replacement = sprintf(
            "## [Unreleased]\n\n## [%s] - %s",
            $version,
            $today
        );

        $updated = preg_replace(
            '/## \[Unreleased\]/i',
            $replacement,
            $contents,
            1
        );

        if ($updated === null) {
            throw new RuntimeException(
                'Unable to update CHANGELOG.md.'
            );
        }

        file_put_contents(
            $path,
            $updated
        );

        $io->success(sprintf(
            'Updated CHANGELOG.md with [%s].',
            $version
        ));
    }
}
