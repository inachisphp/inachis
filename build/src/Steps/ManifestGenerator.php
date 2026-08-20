<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Steps;

use Inachis\Build\BuildStepInterface;
use Inachis\Build\ReleaseWorkspace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final class ManifestGenerator implements BuildStepInterface
{
    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Generating release manifest');

        $manifest = [
            'name' => $workspace->definition->name,
            'version' => $this->getVersion($workspace),
            'build_date' => (new \DateTimeImmutable())
                ->format(DATE_ATOM),
            'persistent' => $workspace->definition->persistent,
            'files' => [],
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $workspace->path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {

            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr(
                    $file->getPathname(),
                    strlen($workspace->path) + 1
                )
            );

            // Do not include the manifest itself
            if ($relative === 'manifest.json') {
                continue;
            }

            $manifest['files'][$relative] = [
                'size' => $file->getSize(),
                'sha256' => hash_file(
                    'sha256',
                    $file->getPathname()
                ),
            ];
        }

        $manifestPath = $workspace->path
            . DIRECTORY_SEPARATOR
            . 'manifest.json';

        file_put_contents(
            $manifestPath,
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            )
        );

        $io->success(
            sprintf(
                'Manifest created (%d files).',
                count($manifest['files'])
            )
        );

        return new ReleaseWorkspace(
            path: $workspace->path,
            definition: $workspace->definition,
            metadata: [
                ...$workspace->metadata,
                'manifest' => $manifestPath,
                'version' => $manifest['version'],
            ],
        );
    }

    /**
     * Gets the version number from composer.json
     *
     * @param ReleaseWorkspace $workspace
     * @return string
     */
    private function getVersion(
        ReleaseWorkspace $workspace
    ): string {
        $versionFile = $workspace->path
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'version.php';

        if (!is_file($versionFile)) {
            return 'dev';
        }

        /** @var array<string, string> $version */
        $version = require $versionFile;

        return $version['version'] ?? 'dev';
    }
}
