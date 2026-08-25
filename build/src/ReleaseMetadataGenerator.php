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

final class ReleaseMetadataGenerator implements BuildStepInterface
{
    public static function priority(): int
    {
        return 0;
    }

    public function execute(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        $io->section('Generating release metadata');

        $archive = $workspace->metadata['archive'] ?? null;

        if (!is_string($archive) || !file_exists($archive)) {
            throw new RuntimeException(
                'Release archive not found.'
            );
        }

        $manifestPath = $workspace->metadata['manifest'] ?? null;

        if (!is_string($manifestPath) || !file_exists($manifestPath)) {
            throw new RuntimeException(
                'Release manifest not found.'
            );
        }

        $manifestContent = file_get_contents($manifestPath);
        if (!is_string($manifestContent)) {
            throw new RuntimeException(
                'Failed to read release manifest.'
            );
        }

        /** @var mixed $manifest */
        $manifest = json_decode(
            $manifestContent,
            true,
            flags: JSON_THROW_ON_ERROR
        );

        if (!is_array($manifest)) {
            throw new RuntimeException(
                'Invalid release manifest structure.'
            );
        }

        $metadata = [
            'name' => is_string($manifest['name'] ?? null) ? $manifest['name'] : '',
            'version' => is_string($manifest['version'] ?? null) ? $manifest['version'] : 'dev',
            'published' => (new \DateTimeImmutable())
                ->format(DATE_ATOM),
            'archive' => basename($archive),
            'sha256' => hash_file(
                'sha256',
                $archive
            ),
            'size' => filesize($archive),
        ];

        $metadataPath = dirname($archive)
            . DIRECTORY_SEPARATOR
            . sprintf(
                '%s.json',
                pathinfo(
                    $archive,
                    PATHINFO_FILENAME
                )
            );

        file_put_contents(
            $metadataPath,
            json_encode(
                $metadata,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            )
        );

        $io->success(
            sprintf(
                'Metadata created: %s',
                basename($metadataPath)
            )
        );

        return new ReleaseWorkspace(
            path: $workspace->path,
            definition: $workspace->definition,
            metadata: [
                ...$workspace->metadata,
                'metadata' => $metadataPath,
            ],
        );
    }
}
