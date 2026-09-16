<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Backup;

use Inachis\Exception\System\BackupException;
use Inachis\Exception\System\InvalidBackupException;
use Inachis\Model\System\BackupManifest;
use JsonException;
use ZipArchive;

final class BackupArchive
{
    private const string MANIFEST_FILE = 'manifest.json';

    private const string DATABASE_FILE = 'database.sql';

    public function create(
        string $filename,
        BackupManifest $manifest,
        string $databaseDump,
    ): void {
        $archive = new ZipArchive();

        if (true !== $archive->open(
            $filename,
            ZipArchive::CREATE | ZipArchive::OVERWRITE,
        )) {
            throw new BackupException(sprintf(
                'Unable to create backup archive "%s".',
                $filename,
            ));
        }

        $archive->addFromString(
            self::MANIFEST_FILE,
            $manifest->toJson(),
        );

        $archive->addFromString(
            self::DATABASE_FILE,
            $databaseDump,
        );

        if (!$archive->close()) {
            throw new BackupException(sprintf(
                'Unable to close backup archive "%s".',
                $filename,
            ));
        }
    }

    public function validate(string $filename): BackupManifest
    {
        $archive = $this->open($filename);

        try {
            if (false === $archive->locateName(self::MANIFEST_FILE)) {
                throw new InvalidBackupException(
                    'The backup does not contain a manifest.',
                );
            }

            if (false === $archive->locateName(self::DATABASE_FILE)) {
                throw new InvalidBackupException(
                    'The backup does not contain a database dump.',
                );
            }

            $manifestJson = $archive->getFromName(self::MANIFEST_FILE);

            if (false === $manifestJson) {
                throw new InvalidBackupException(
                    'Unable to read the backup manifest.',
                );
            }

            try {
                /** @var array<string, mixed> $manifest */
                $manifest = json_decode(
                    $manifestJson,
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException $exception) {
                throw new InvalidBackupException(
                    'The backup manifest contains invalid JSON.',
                    previous: $exception,
                );
            }

            return $this->createManifestFromArray($manifest);
        } finally {
            $archive->close();
        }
    }

    private function open(string $filename): ZipArchive
    {
        if (!is_file($filename)) {
            throw new InvalidBackupException(sprintf(
                'Backup file "%s" does not exist.',
                $filename,
            ));
        }

        $archive = new ZipArchive();

        if (true !== $archive->open($filename)) {
            throw new InvalidBackupException(sprintf(
                'Unable to open backup archive "%s".',
                $filename,
            ));
        }

        return $archive;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function createManifestFromArray(array $manifest): BackupManifest
    {
        if (!isset(
            $manifest['format_version'],
            $manifest['inachis'],
            $manifest['created_at'],
            $manifest['php_version'],
            $manifest['database'],
            $manifest['migrations'],
        )) {
            throw new InvalidBackupException(
                'The backup manifest is missing required fields.',
            );
        }

        if (!is_array($manifest['inachis'])) {
            throw new InvalidBackupException(
                'The Inachis version information is invalid.',
            );
        }

        if (!is_array($manifest['database'])) {
            throw new InvalidBackupException(
                'The database information is invalid.',
            );
        }

        if (!is_array($manifest['migrations'])) {
            throw new InvalidBackupException(
                'The migration information is invalid.',
            );
        }

        try {
            $createdAt = new \DateTimeImmutable(
                (string) $manifest['created_at'],
            );
        } catch (\Exception $exception) {
            throw new InvalidBackupException(
                'The backup creation date is invalid.',
                previous: $exception,
            );
        }

        return new BackupManifest(
            formatVersion: (int) $manifest['format_version'],
            inachis: array_map(
                static fn (mixed $value): string => (string) $value,
                $manifest['inachis'],
            ),
            createdAt: $createdAt,
            phpVersion: (string) $manifest['php_version'],
            databasePlatform: (string) ($manifest['database']['platform'] ?? ''),
            databaseVersion: (string) ($manifest['database']['version'] ?? ''),
            migrationVersion: (string) ($manifest['migrations']['version'] ?? ''),
        );
    }
}
