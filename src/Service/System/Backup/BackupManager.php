<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Backup;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Inachis\Model\System\BackupManifest;
use Inachis\Service\System\Backup\BackupArchive;
use Inachis\Service\System\VersionService;
use RuntimeException;
use Symfony\Component\Process\Process;

final class BackupManager
{
    private const int BACKUP_FORMAT_VERSION = 1;

    public function __construct(
        private readonly Connection $connection,
        private readonly BackupArchive $archive,
        private readonly VersionService $versionService,
        private readonly DependencyFactory $migrationDependencyFactory,
    ) {
    }

    public function create(string $filename): void
    {
        $manifest = new BackupManifest(
            formatVersion: self::BACKUP_FORMAT_VERSION,
            inachis: $this->versionService->getAll(),
            createdAt: new \DateTimeImmutable(),
            phpVersion: PHP_VERSION,
            databasePlatform: 'mariadb',
            databaseVersion: $this->getDatabaseVersion(),
            migrationVersion: $this->getMigrationVersion(),
        );

        $this->archive->create(
            $filename,
            $manifest,
            $this->dumpDatabase(),
        );
    }

    private function dumpDatabase(): string
    {
        $params = $this->connection->getParams();

        $host = $params['host'] ?? 'localhost';
        $port = (int) ($params['port'] ?? 3306);
        $user = $params['user'] ?? null;
        $password = $params['password'] ?? null;
        $database = $params['dbname'] ?? null;

        if ($user === null || $database === null) {
            throw new RuntimeException(
                'Unable to determine the database credentials from the Doctrine connection.',
            );
        }

        $optionFile = tempnam(sys_get_temp_dir(), 'inachis-backup-');

        if ($optionFile === false) {
            throw new RuntimeException(
                'Unable to create a temporary MySQL configuration file.',
            );
        }

        try {
            $contents = sprintf(
                "[client]\nuser=%s\npassword=%s\nhost=%s\nport=%d\n",
                $this->escapeOptionValue($user),
                $this->escapeOptionValue((string) $password),
                $this->escapeOptionValue($host),
                $port,
            );

            if (file_put_contents($optionFile, $contents) === false) {
                throw new RuntimeException(
                    'Unable to write the temporary MySQL configuration file.',
                );
            }

            chmod($optionFile, 0600);

            $process = new Process([
                'mysqldump',
                '--defaults-extra-file='.$optionFile,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--hex-blob',
                '--default-character-set=utf8mb4',
                $database,
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException(sprintf(
                    "Database backup failed:\n%s",
                    trim($process->getErrorOutput()),
                ));
            }

            return $process->getOutput();
        } finally {
            if (is_file($optionFile)) {
                unlink($optionFile);
            }
        }
    }

    private function getDatabaseVersion(): string
    {
        return (string) $this->connection
            ->executeQuery('SELECT VERSION()')
            ->fetchOne();
    }

    private function getMigrationVersion(): string
    {
        $executedMigrations = $this->migrationDependencyFactory
            ->getMetadataStorage()
            ->getExecutedMigrations();

        if ($executedMigrations->count() === 0) {
            return '';
        }

        return $executedMigrations
            ->getLast()
            ->getVersion()
            ->getVersion();
    }

    private function escapeOptionValue(string $value): string
    {
        return str_replace(
            ["\\", "\n", "\r"],
            ["\\\\", "\\n", "\\r"],
            $value,
        );
    }
}
