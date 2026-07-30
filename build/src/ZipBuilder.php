<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use ZipArchive;

final class ZipBuilder implements BuildStepInterface
{
    public static function priority(): int
    {
        return 10;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Creating release archive');

        $manifestPath = $workspace->path
            . DIRECTORY_SEPARATOR
            . 'manifest.json';

        if (!file_exists($manifestPath)) {
            throw new RuntimeException(
                'Manifest does not exist.'
            );
        }

        $manifest = json_decode(
            file_get_contents($manifestPath),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $version = $manifest['version'] ?? 'unknown';

        $dist = dirname($workspace->path)
            . DIRECTORY_SEPARATOR
            . 'dist';

        if (!is_dir($dist) && !mkdir($dist, 0775, true) && !is_dir($dist)) {
            throw new RuntimeException(
                'Unable to create dist directory.'
            );
        }

        $archive = $dist
            . DIRECTORY_SEPARATOR
            . sprintf(
                'inachis-%s.zip',
                $version
            );

        if (file_exists($archive)) {
            unlink($archive);
        }

        $zip = new ZipArchive();

        if ($zip->open($archive, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(
                'Unable to create zip archive.'
            );
        }

        $this->addDirectory(
            $zip,
            $workspace->path
        );

        $zip->close();

        $io->success(
            sprintf(
                'Created %s',
                basename($archive)
            )
        );

        return new ReleaseWorkspace(
            path: $workspace->path,
            definition: $workspace->definition,
            metadata: [
                ...$workspace->metadata,
                'archive' => $archive,
            ],
        );
    }

    private function addDirectory(
        ZipArchive $zip,
        string $directory,
    ): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {

            $path = substr(
                $file->getPathname(),
                strlen($directory) + 1
            );

            if ($file->isDir()) {
                $zip->addEmptyDir($path);
                continue;
            }

            if (!$zip->addFile(
                $file->getPathname(),
                $path
            )) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to add "%s" to archive.',
                        $path
                    )
                );
            }
        }
    }
}
