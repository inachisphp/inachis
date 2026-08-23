<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\MessageHandler;

use Doctrine\DBAL\Connection;
use Inachis\Message\CreateBackupMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for CreateBackupMessage using Doctrine DBAL.
 */
#[AsMessageHandler]
class CreateBackupHandler
{
    /**
     * Tables to skip during user-data exports.
     */
    private const EXCLUDED_TABLES = [
        'doctrine_migration_versions',
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(CreateBackupMessage $message): string
    {
        if (!is_dir($message->outputDir) && !mkdir($message->outputDir, 0755, true) && !is_dir($message->outputDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $message->outputDir));
        }

        $dbName = $this->connection->getDatabase() ?? 'inachis';
        $filename = sprintf('backup_%s_%s.sql.gz', $dbName, (new \DateTimeImmutable())->format('Y-m-d_His'));
        $filePath = rtrim($message->outputDir, '/') . '/' . $filename;

        // Open compressed stream (Level 9 compression)
        $gz = gzopen($filePath, 'wb9');
        if ($gz === false) {
            throw new \RuntimeException(sprintf('Unable to create backup file at "%s"', $filePath));
        }

        // Header setup
        gzwrite($gz, "-- Inachis Data-Only Database Backup\n");
        gzwrite($gz, sprintf("-- Generated: %s\n\n", (new \DateTimeImmutable())->format('Y-m-d H:i:s')));
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        foreach ($tables as $table) {
            if (in_array($table, self::EXCLUDED_TABLES, true)) {
                continue;
            }

            gzwrite($gz, sprintf("-- Data for table `%s` --\n", $table));

            $quotedTable = $this->connection->quoteIdentifier($table);
            $result = $this->connection->executeQuery(sprintf('SELECT * FROM %s', $quotedTable));

            // Iterate row-by-row to keep memory usage minimal (O(1))
            while ($row = $result->fetchAssociative()) {
                $columns = array_map(
                    fn (string $col) => $this->connection->quoteIdentifier($col),
                    array_keys($row)
                );

                $values = array_map(
                    fn ($val) => match (true) {
                        $val === null => 'NULL',
                        is_bool($val) => $val ? '1' : '0',
                        is_int($val), is_float($val) => (string) $val,
                        default => $this->connection->quote((string) $val),
                    },
                    array_values($row)
                );

                $sql = sprintf(
                    "INSERT INTO %s (%s) VALUES (%s);\n",
                    $quotedTable,
                    implode(', ', $columns),
                    implode(', ', $values)
                );

                gzwrite($gz, $sql);
            }

            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS = 1;\n");
        gzclose($gz);

        return $filePath;
    }
}
