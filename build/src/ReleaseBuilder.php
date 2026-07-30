<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class ReleaseBuilder
{
    public function __construct(
        private string $projectRoot,
    ) {}

    public function build(
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Preparing workspace');
        $definition = (new ReleaseDefinitionLoader(
            $this->projectRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'release.json'
        ))->load();

        $workspace = $this->projectRoot
            . DIRECTORY_SEPARATOR
            . 'build'
            . DIRECTORY_SEPARATOR
            . 'workspace';

        $this->prepareWorkspace($workspace);

        foreach ($definition->contents as $entry) {
            $this->copyEntry($entry, $workspace);
            $io->writeln(sprintf(
                ' <info>✓</info> %s',
                $entry->path
            ));
        }

        return new ReleaseWorkspace(
            path: $workspace,
            definition: $definition,
        );
    }

    private function prepareWorkspace(string $workspace): void
    {
        $this->removeDirectory($workspace);

        if (!mkdir($workspace, 0775, true) && !is_dir($workspace)) {
            throw new RuntimeException(sprintf(
                'Unable to create workspace "%s".',
                $workspace
            ));
        }
    }

    private function copyEntry(
        ReleaseEntry $entry,
        string $workspace,
    ): void {
        $source = $this->projectRoot
            . DIRECTORY_SEPARATOR
            . $entry->path;

        $destination = $workspace
            . DIRECTORY_SEPARATOR
            . $entry->path;

        if (!file_exists($source)) {
            if ($entry->optional) {
                return;
            }

            throw new RuntimeException(sprintf(
                'Release entry "%s" does not exist.',
                $entry->path
            ));
        }

        match ($entry->type) {
            ReleaseEntryType::Directory => $this->copyDirectory(
                $source,
                $destination
            ),
            ReleaseEntryType::File => $this->copyFile(
                $source,
                $destination
            ),
        };
    }

    private function copyDirectory(
        string $source,
        string $destination,
    ): void {
        if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
            throw new RuntimeException(sprintf(
                'Unable to create directory "%s".',
                $destination
            ));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $source,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination
                . DIRECTORY_SEPARATOR
                . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!mkdir($target, 0775, true) && !is_dir($target)) {
                    throw new RuntimeException(sprintf(
                        'Unable to create directory "%s".',
                        $target
                    ));
                }

                continue;
            }

            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException(sprintf(
                    'Failed copying "%s".',
                    $item->getPathname()
                ));
            }
        }
    }

    private function copyFile(
        string $source,
        string $destination,
    ): void {
        $directory = dirname($destination);

        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf(
                'Unable to create directory "%s".',
                $directory
            ));
        }

        if (!copy($source, $destination)) {
            throw new RuntimeException(sprintf(
                'Failed copying "%s".',
                $source
            ));
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if (!rmdir($item->getPathname())) {
                    throw new RuntimeException(sprintf(
                        'Unable to remove directory "%s".',
                        $item->getPathname()
                    ));
                }

                continue;
            }

            if (!unlink($item->getPathname())) {
                throw new RuntimeException(sprintf(
                    'Unable to remove file "%s".',
                    $item->getPathname()
                ));
            }
        }

        if (!rmdir($directory)) {
            throw new RuntimeException(sprintf(
                'Unable to remove directory "%s".',
                $directory
            ));
        }
    }
}
