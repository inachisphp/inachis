<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'inachis:migrate-uuid-storage',
    description: 'UUID CHAR(36) to BINARY(16) migration with verification and reporting'
)]
final class MigrateUuidStorageCommand extends Command
{
    private const CHUNK_SIZE = 2000;

    public function __construct(private readonly Connection $db)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'execute',
                null,
                InputOption::VALUE_NONE,
                'Actually execute migration'
            )
            ->addOption(
                'resume',
                null,
                InputOption::VALUE_NONE,
                'Resume a previous migration'
            )
            ->addOption(
                'table',
                null,
                InputOption::VALUE_OPTIONAL,
                'Process a single table'
            )
            ->addOption(
                'phase',
                null,
                InputOption::VALUE_OPTIONAL,
                'Stop after a phase (0-5)',
                '5'
            )
            ->addOption(
                'verify-only',
                null,
                InputOption::VALUE_NONE,
                'Run verification only'
            )
            ->addOption(
                'force-snapshot',
                null,
                InputOption::VALUE_NONE,
                'Recreate schema snapshot'
            )
            ->addOption(
                'cleanup-old',
                null,
                InputOption::VALUE_NONE,
                'Drop *_old UUID columns created during migration'
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $execute = (bool) $input->getOption('execute');
        $resume = (bool) $input->getOption('resume');
        $verifyOnly = (bool) $input->getOption('verify-only');
        $forceSnapshot = (bool) $input->getOption('force-snapshot');
        $cleanupOld = (bool) $input->getOption('cleanup-old');

        $singleTable = $input->getOption('table');

        $maxPhase = (int) $input->getOption('phase');

        $dryRun = !$execute;

        $output->writeln('');
        $output->writeln('<info>UUID CHAR(36) → BINARY(16)</info>');
        $output->writeln(
            $dryRun
                ? '<comment>DRY RUN</comment>'
                : '<error>EXECUTE MODE</error>'
        );
        $output->writeln('');

        try {
            if ($cleanupOld) {
                $output->writeln('');
                $output->writeln('<info>UUID cleanup mode (dropping *_old columns only)</info>');
                $output->writeln('');

                return $this->cleanupOldColumns($output);
            }

            /**
             * =====================================================
             * Phase 0
             * Snapshot schema
             * =====================================================
             */
            if ($maxPhase >= 0) {

                $output->writeln(
                    '<info>[Phase 0] Schema Snapshot</info>'
                );

                if (
                    $forceSnapshot
                    || !$this->snapshotExists()
                ) {
                    $this->snapshotSchema($output);
                } else {
                    $output->writeln(
                        'Existing snapshot detected.'
                    );
                }

                $output->writeln(
                    '<fg=green>✓ Phase 0 complete</>'
                );
            }

            /**
             * =====================================================
             * Phase 1
             * Discover UUID columns
             * =====================================================
             */
            $output->writeln(
                '<info>[Phase 1] UUID Discovery</info>'
            );

            $uuidMap = $this->discoverUuidColumns();

            if ($singleTable !== null) {

                if (!isset($uuidMap[$singleTable])) {
                    throw new \RuntimeException(
                        "Unknown table: {$singleTable}"
                    );
                }

                $uuidMap = [
                    $singleTable => $uuidMap[$singleTable]
                ];
            }

            $columnCount = 0;

            foreach ($uuidMap as $columns) {
                $columnCount += count($columns);
            }

            $output->writeln(sprintf(
                'Discovered %d UUID columns across %d tables',
                $columnCount,
                count($uuidMap)
            ));

            $output->writeln(
                '<fg=green>✓ Phase 1 complete</>'
            );

            if ($maxPhase === 1) {
                return Command::SUCCESS;
            }

            /**
             * =====================================================
             * Phase 2
             * Create _bin columns
             * =====================================================
             */
            $output->writeln(
                '<info>[Phase 2] Create Binary Columns</info>'
            );

            foreach ($uuidMap as $table => $columns) {
                $output->writeln("  {$table}");
                if (!$verifyOnly) {
                    $this->addBinaryColumns(
                        $table,
                        $columns,
                        $dryRun
                    );
                }
                if (!$dryRun) {
                    $this->verifyBinaryColumnsExist(
                        $table,
                        $columns
                    );
                } else {
                    foreach ($columns as $column) {
                        $output->writeln("    would verify {$table}.{$column}_bin exists");
                    }
                }
            }

            $output->writeln(
                '<fg=green>✓ Phase 2 complete</>'
            );

            if ($maxPhase === 2) {
                return Command::SUCCESS;
            }

            /**
             * =====================================================
             * Phase 3
             * Backfill
             * =====================================================
             */
            $output->writeln(
                '<info>[Phase 3] Backfill Binary Data</info>'
            );

            if (!$verifyOnly && !$dryRun) {

                foreach ($uuidMap as $table => $columns) {

                    foreach ($columns as $column) {

                        $rows = $this->backfillColumn(
                            $table,
                            $column
                        );

                        $output->writeln(sprintf(
                            '  %s.%s => %d rows',
                            $table,
                            $column,
                            $rows
                        ));
                    }
                }
            }

            $output->writeln(
                '<fg=green>✓ Phase 3 complete</>'
            );

            if ($maxPhase === 3) {
                return Command::SUCCESS;
            }

            /**
             * =====================================================
             * Phase 4
             * Conversion Verification
             * =====================================================
             */
            $output->writeln(
                '<info>[Phase 4] Verify Conversion</info>'
            );

            foreach ($uuidMap as $table => $columns) {
                foreach ($columns as $column) {
                    if (!$dryRun) {
                        $this->verifyNoNullBinaryValues(
                            $table,
                            $column
                        );
                        $this->verifyBinaryMatchesUuid(
                            $table,
                            $column
                        );
                        $this->verifyBinaryLength(
                            $table,
                            $column
                        );
                        $output->writeln(sprintf(
                            '  ✓ %s.%s',
                            $table,
                            $column
                        ));
                    } else {
                        $output->writeln(sprintf(
                            'Skipped verifying %s.%s',
                            $table,
                            $column
                        ));
                    }
                }
            }

            $output->writeln(
                '<fg=green>✓ Phase 4 complete</>'
            );

            if ($maxPhase === 4) {
                return Command::SUCCESS;
            }

            /**
             * =====================================================
             * Phase 5
             * Shadow FK Validation
             * =====================================================
             */
            $output->writeln(
                '<info>[Phase 5] Shadow FK Validation</info>'
            );
            $foreignKeys = $this->discoverForeignKeys();
            foreach ($foreignKeys as $fk) {
                if (!$dryRun) {
                    $this->verifyForeignKeyShadow($fk);
                    $output->writeln(sprintf(
                        '  ✓ %s (%s)',
                        $fk['TABLE_NAME'],
                        implode(', ', $fk['columns']) // Changed to read the array of columns
                    ));
                }
            }

            $output->writeln(
                '<fg=green>✓ Phase 5 complete</>'
            );

            $output->writeln('');
            $output->writeln(
                '<fg=green>All verification phases completed successfully.</>'
            );
            $output->writeln(
                '<comment>Safe to continue with FK drop/swap phases.</comment>'
            );
            $output->writeln('');

            if ($maxPhase >= 5) {
                $foreignKeys = $this->discoverForeignKeys();
                $this->dropForeignKeys($foreignKeys, $output);
                if ($maxPhase === 6) {
                    return Command::SUCCESS;
                }

                $this->swap($uuidMap, $output);
                if ($maxPhase === 7) {
                    return Command::SUCCESS;
                }

                $this->rebuildPrimaryKeys($uuidMap, $output);
                if ($maxPhase === 8) {
                    return Command::SUCCESS;
                }

                $this->rebuildIndexes($uuidMap, $output);
                if ($maxPhase === 9) {
                    return Command::SUCCESS;
                }

                $this->rebuildForeignKeys($foreignKeys, $output);
                if ($maxPhase === 10) {
                    return Command::SUCCESS;
                }
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $output->writeln('');
            $output->writeln(
                '<error>MIGRATION ABORTED</error>'
            );

            $output->writeln(
                sprintf(
                    '<error>%s</error>',
                    $e->getMessage()
                )
            );

            return Command::FAILURE;
        }
    }

    /**
     * Check whether a schema snapshot already exists.
     */
    private function snapshotExists(): bool
    {
        $count = (int) $this->db->fetchOne("
            SELECT COUNT(*)
            FROM uuid_migration_snapshot
            WHERE snapshot_type = 'table'
        ");

        return $count > 0;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function discoverForeignKeys(): array
    {
        $rows = $this->db->fetchAllAssociative("
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION
        ");

        $fks = [];
        foreach ($rows as $row) {
            $cName = $row['CONSTRAINT_NAME'];

            if (!isset($fks[$cName])) {
                $fks[$cName] = [
                    'constraint' => $cName,
                    'TABLE_NAME' => $row['TABLE_NAME'],
                    'REFERENCED_TABLE_NAME' => $row['REFERENCED_TABLE_NAME'],
                    'columns' => [],
                    'referenced_columns' => []
                ];
            }

            $fks[$cName]['columns'][] = $row['COLUMN_NAME'];
            $fks[$cName]['referenced_columns'][] = $row['REFERENCED_COLUMN_NAME'];
        }

        return array_values($fks);
    }

    /**
     * Return all columns for a table with basic metadata.
     */
    private function discoverColumns(string $table): array
    {
        $rows = $this->db->fetchAllAssociative("
            SELECT
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                EXTRA,
                COLUMN_KEY
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$table]);

        $columns = [];

        foreach ($rows as $row) {
            $columns[] = [
                'name' => $row['COLUMN_NAME'],
                'type' => $row['COLUMN_TYPE'],
                'nullable' => $row['IS_NULLABLE'] === 'YES',
                'default' => $row['COLUMN_DEFAULT'],
                'extra' => $row['EXTRA'],
                'key' => $row['COLUMN_KEY'], // PRI / MUL / UNI
            ];
        }

        return $columns;
    }

    /**
     * Return primary key columns (supports composite PKs).
     */
    private function discoverPrimaryKey(string $table): array
    {
        $snapshot = $this->getSnapshotPayload($table);
        if ($snapshot && isset($snapshot['primaryKey'])) {
            return $snapshot['primaryKey'];
        }

        return [];
    }

    /**
     * Return foreign keys for a specific table.
     */
    private function discoverForeignKeysForTable(string $table): array
    {
        $rows = $this->db->fetchAllAssociative("
            SELECT
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION
        ", [$table]);

        $fks = [];

        foreach ($rows as $row) {
            $fks[] = [
                'column' => $row['COLUMN_NAME'],
                'referenced_table' => $row['REFERENCED_TABLE_NAME'],
                'referenced_column' => $row['REFERENCED_COLUMN_NAME'],
                'constraint' => $row['CONSTRAINT_NAME'],
            ];
        }

        return $fks;
    }

    /**
     * Return index metadata grouped by index name.
     */
    private function discoverIndexes(string $table): array
    {
        $snapshot = $this->getSnapshotPayload($table);
        if ($snapshot && isset($snapshot['indexes'])) {
            return $snapshot['indexes'];
        }

        return [];
    }

    /**
     * Check if a column is nullable.
     */
    private function isNullable(string $table, string $column): bool
    {
        $value = $this->db->fetchOne("
            SELECT IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]);

        return $value === 'YES';
    }

    /**
     * Execute SQL only if a column does not already exist.
     */
    private function execIfMissing(
        string $table,
        string $column,
        string $sql,
        bool $dryRun
    ): void {
        $exists = (int) $this->db->fetchOne("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]);

        if ($exists > 0) {
            return;
        }

        if ($dryRun) {
            return;
        }

        $this->db->executeStatement($sql);
    }

    private function dropForeignKey(string $table, string $constraint): void
    {
        $this->db->executeStatement("
            ALTER TABLE `$table` DROP FOREIGN KEY `$constraint`
        ");
    }

    private function createForeignKey(
        string $table,
        array $columns,
        string $refTable,
        array $refColumns,
        string $constraint
    ): void {
        $localCols = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
        $foreignCols = implode(', ', array_map(fn($c) => "`{$c}`", $refColumns));

        $sql = "
            ALTER TABLE `$table`
            ADD CONSTRAINT `$constraint`
            FOREIGN KEY ($localCols)
            REFERENCES `$refTable`($foreignCols)
        ";

        $this->db->executeStatement($sql);
    }

    private function dropPrimaryKey(string $table): void
    {
        $this->db->executeStatement("
            ALTER TABLE `$table` DROP PRIMARY KEY
        ");
    }

    private function createPrimaryKey(string $table, array $columns): void
    {
        $binCols = array_map(
            fn($c) => "`{$c}`",
            $columns
        );

        $sql = sprintf(
            "ALTER TABLE `%s` ADD PRIMARY KEY (%s)",
            $table,
            implode(',', $binCols)
        );

        $this->db->executeStatement($sql);
    }

    private function dropIndex(string $table, string $index): void
    {
        if ($index === 'PRIMARY') {
            return;
        }

        $this->db->executeStatement("
            ALTER TABLE `$table` DROP INDEX `$index`
        ");
    }

    private function createIndex(string $table, array $index): void
    {
        $name = $index['name'];
        $columns = $index['columns'];

        $binCols = array_map(
            fn($c) => "`{$c}`",
            $columns
        );

        // Determine the correct index modifier type
        $typeModifier = '';
        if ($index['type'] === 'FULLTEXT') {
            $typeModifier = 'FULLTEXT';
        } elseif ($index['unique']) {
            $typeModifier = 'UNIQUE';
        }

        $sql = sprintf(
            "ALTER TABLE `%s` ADD %s INDEX `%s` (%s)",
            $table,
            $typeModifier,
            $name,
            implode(',', $binCols)
        );

        $this->db->executeStatement($sql);
    }

    private function swapUuidColumns(string $table, string $column, bool $nullable): void
    {
        $temp = "{$column}_old";
        $nullNullability = $nullable ? 'NULL' : 'NOT NULL';

        $sql = "
            ALTER TABLE `$table`
            CHANGE `$column` `$temp` CHAR(36) $nullNullability,
            CHANGE `{$column}_bin` `$column` BINARY(16) $nullNullability
        ";

        $this->db->executeStatement($sql);
    }

    private function resolveConstraintName(array $fk): string
    {
        return $this->db->fetchOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [
            $fk['TABLE_NAME'],
            $fk['COLUMN_NAME']
        ]);
    }

    //
    // Functions for performing migration below
    // =========================================
    ///


    /**
     * Step 0: Snapshot the current schema
     *
     * @param OutputInterface $output
     */
    private function snapshotSchema(OutputInterface $output): void
    {
        $output->writeln('Creating schema snapshot...');

        $tables = $this->db->fetchFirstColumn("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
        ");

        foreach ($tables as $table) {

            $snapshot = [
                'columns' => $this->discoverColumns($table),
                'primaryKey' => $this->discoverPrimaryKey($table),
                'foreignKeys' => $this->discoverForeignKeysForTable($table),
                'indexes' => $this->discoverIndexes($table),
            ];

            $this->db->insert('uuid_migration_snapshot', [
                'snapshot_type' => 'table',
                'table_name' => $table,
                'payload' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function getSnapshotPayload(string $table): ?array
    {
        $payload = $this->db->fetchOne("
            SELECT payload
            FROM uuid_migration_snapshot
            WHERE snapshot_type = 'table'
            AND table_name = ?
        ", [$table]);

        if (!$payload) {
            return null;
        }

        return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Step 1: Discover UUID columns
     */
    private function discoverUuidColumns(): array
    {
        // Pass 1: Find all base tables that explicitly own native CHAR(36)/VARCHAR(36) primary or unique columns
        $rows = $this->db->fetchAllAssociative("
            SELECT DISTINCT
                TABLE_NAME,
                COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND (
                COLUMN_TYPE LIKE 'char(36)%'
                OR COLUMN_TYPE LIKE 'varchar(36)%'
                OR (DATA_TYPE IN ('char', 'varchar') AND CHARACTER_MAXIMUM_LENGTH = 36)
            )
        ");

        $map = [];
        $baseTables = [];
        foreach ($rows as $row) {
            $table = $row['TABLE_NAME'] ?? $row['table_name'];
            $column = $row['COLUMN_NAME'] ?? $row['column_name'];

            $map[$table][] = $column;
            $baseTables[strtolower($table)] = true;
        }

        // Pass 2: Grab EVERY SINGLE foreign key relationship in the database.
        // If it references a table we know is transforming, we MUST include the local column
        // NO MATTER WHAT ITS CURRENT TYPE IS (char(36), varchar(255), text, etc.)
        $fkRows = $this->db->fetchAllAssociative("
            SELECT
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($fkRows as $fk) {
            $refTable = $fk['REFERENCED_TABLE_NAME'] ?? $fk['referenced_table_name'] ?? null;
            $tableName = $fk['TABLE_NAME'] ?? $fk['table_name'] ?? null;
            $columnName = $fk['COLUMN_NAME'] ?? $fk['column_name'] ?? null;

            if ($refTable !== null && $tableName !== null && $columnName !== null) {
                // Case-insensitive check to see if the target table is undergoing a migration
                if (isset($baseTables[strtolower($refTable)])) {
                    $map[$tableName][] = $columnName;
                }
            }
        }

        // Clean up duplicates, sort columns, and sort tables
        foreach ($map as $table => $columns) {
            $columns = array_unique($columns);
            sort($columns);
            $map[$table] = $columns;
        }

        ksort($map);

        return $map;
    }

    /**
     * Step 2.1: Add _bin columns for each column being replaced
     *
     * @param string $table
     * @param array<> $uuidColumns
     * @param bool $dryRun
     */
    private function addBinaryColumns(
        string $table,
        array $uuidColumns,
        bool $dryRun
    ): void {
        foreach ($uuidColumns as $column) {

            $nullable = $this->isNullable($table, $column);

            $sql = sprintf(
                "ALTER TABLE `%s`
             ADD COLUMN IF NOT EXISTS `%s_bin` BINARY(16) %s AFTER %s",
                $table,
                $column,
                $nullable ? 'NULL' : 'NOT NULL',
                $column
            );

            $this->execIfMissing($table, "{$column}_bin", $sql, $dryRun);
        }
    }

    /**
     * Step 2.2: Verify _bin columns exist
     */
    private function verifyBinaryColumnsExist(
        string $table,
        array $columns
    ): void {
        foreach ($columns as $column) {
            $exists = (int)$this->db->fetchOne("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, "{$column}_bin"]);

            if ($exists === 0) {
                throw new \RuntimeException(
                    "$table.{$column}_bin missing"
                );
            }
        }
    }

    /**
     * Step 3: Backfill _bin columns from ID columns
     *
     * @param string $table
     * @param string $column
     * @return int
     */
    private function backfillColumn(
        string $table,
        string $column
    ): int {
        $bin = "{$column}_bin";
        $total = 0;

        do {
            $affected = $this->db->executeStatement("
            UPDATE `$table`
            SET `$bin` = UNHEX(REPLACE(`$column`, '-', ''))
            WHERE `$column` IS NOT NULL
            AND (`$bin` IS NULL OR `$bin` = 0x00000000000000000000000000000000)
            LIMIT " . self::CHUNK_SIZE
            );
            $total += $affected;
        } while ($affected > 0);

        return $total;
    }

    /**
     * Step 4.1: Verify there are no empty _bin fields
     *
     * @param string $table
     * @param string $column
     */
    private function verifyNoNullBinaryValues(
        string $table,
        string $column
    ): void {
        $count = (int)$this->db->fetchOne("
        SELECT COUNT(*)
        FROM `$table`
        WHERE `$column` IS NOT NULL
        AND `{$column}_bin` IS NULL
    ");

        if ($count > 0) {
            throw new \RuntimeException(
                "$table.$column has $count NULL binary values"
            );
        }
    }

    /**
     * Step 4.2: Verify UUID_BINARY values relate to UUIDs
     *
     * @param string $table
     * @param string $column
     */
    private function verifyBinaryMatchesUuid(
        string $table,
        string $column
    ): void {
        $count = (int)$this->db->fetchOne("
            SELECT COUNT(*)
            FROM `$table`
            WHERE `$column` IS NOT NULL
            AND `{$column}_bin`
                <> UNHEX(REPLACE(`$column`, '-', ''))
        ");
        if ($count > 0) {
            throw new \RuntimeException(
                "$table.$column has $count mismatches"
            );
        }
    }

    /**
     * Step 4.3: Check binary length is correct
     *
     * @param string $table
     * @param string $column
     */
    private function verifyBinaryLength(
        string $table,
        string $column
    ): void {
        $count = (int)$this->db->fetchOne("
        SELECT COUNT(*)
        FROM `$table`
        WHERE `$column` IS NOT NULL
        AND LENGTH(`{$column}_bin`) <> 16
    ");
        if ($count > 0) {
            throw new \RuntimeException(
                "$table.$column contains invalid binary lengths"
            );
        }
    }

    /**
     * Step 4.4: Verify FK Shadow
     * * @param array $fk
     */
    private function verifyForeignKeyShadow(array $fk): void
    {
        $table = $fk['TABLE_NAME'];
        $refTable = $fk['REFERENCED_TABLE_NAME'];

        $joinConditions = [];
        $whereConditions = [];

        foreach ($fk['columns'] as $index => $column) {
            $refColumn = $fk['referenced_columns'][$index];
            $shadowColumn = "{$column}_bin";

            // GUARD CLAUSE: If the shadow column doesn't exist, this table/column
            // wasn't part of this migration run (e.g., already migrated). Skip validation.
            $columnExists = (int) $this->db->fetchOne("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
            ", [$table, $shadowColumn]);

            if ($columnExists === 0) {
                return;
            }

            // Match shadow binary columns to the unhexed original targets
            $joinConditions[] = "t.`{$shadowColumn}` = UNHEX(REPLACE(r.`$refColumn`, '-', ''))";
            $whereConditions[] = "t.`{$shadowColumn}` IS NOT NULL";
        }

        $joinSql = implode(' AND ', $joinConditions);
        $whereSql = implode(' AND ', $whereConditions);

        $count = (int)$this->db->fetchOne("
            SELECT COUNT(*)
            FROM `$table` t
            LEFT JOIN `$refTable` r ON $joinSql
            WHERE $whereSql
            AND r.`{$fk['referenced_columns'][0]}` IS NULL
        ");

        if ($count > 0) {
            throw new \RuntimeException(
                "{$table} shadow FK validation failed for constraint: {$fk['constraint']}"
            );
        }
    }

    /**
     * Step 5: Drop FKs
     */
    private function dropForeignKeys(array $foreignKeys, OutputInterface $output): void
    {
        $output->writeln('<info>[Phase 6] Drop Foreign Keys</info>');

        $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($foreignKeys as $fk) {
                $this->dropForeignKey($fk['TABLE_NAME'], $fk['constraint']);
                $output->writeln(sprintf('  dropped FK constraint %s on %s', $fk['constraint'], $fk['TABLE_NAME']));
            }
        } finally {
            $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $output->writeln('<fg=green>✓ Phase 6 complete</>');
    }

    /**
     * Step 6: Swap UUID and UUID_BINARY columns over
     *
     * @param array $uuidMap
     * @param OutputInterface $output
     */
    private function swap(array $uuidMap, OutputInterface $output): void
    {
        $output->writeln('<info>[Phase 7] Swap CHAR → BINARY</info>');

        foreach ($uuidMap as $table => $columns) {

            foreach ($columns as $column) {
                // Fetch the nullability of the original column before swapping it
                $nullable = $this->isNullable($table, $column);

                $this->swapUuidColumns($table, $column, $nullable);

                $output->writeln("  swapped {$table}.{$column}");
            }
        }

        $output->writeln('<fg=green>✓ Phase 7 complete</>');
    }

    /**
     * Step: 7: Rebuild Primary Keys
     *
     * @param array $uuidMap
     * @param OutputInterface $output
     */
    private function rebuildPrimaryKeys(array $uuidMap, OutputInterface $output): void
    {
        $output->writeln('<info>[Phase 8] Rebuild Primary Keys</info>');

        foreach ($uuidMap as $table => $columns) {
            $pk = $this->discoverPrimaryKey($table);

            if (empty($pk)) {
                // FALLBACK SAFETY: If it didn't find a PK in the snapshot,
                // but the table is named 'image' or has an 'id' column being migrated, force it
                if (in_array('id', $columns, true)) {
                    $pk = ['id'];
                } else {
                    continue;
                }
            }

            try {
                $this->dropPrimaryKey($table);
            } catch (\Throwable $e) {
                // Suppress if already dropped
            }

            $this->createPrimaryKey($table, $pk);
            $output->writeln("  rebuilt PK {$table} on (" . implode(', ', $pk) . ")");
        }

        $output->writeln('<fg=green>✓ Phase 8 complete</>');
    }

    /**
     * Step 8: Rebuild Indexes
     *
     * @param array $uuidMap
     * @param OutputInterface $output
     */
    private function rebuildIndexes(array $uuidMap, OutputInterface $output): void
    {
        $output->writeln('<info>[Phase 9] Rebuild Indexes</info>');

        // 1. Rebuild standard snapshot indexes
        foreach ($uuidMap as $table => $_columns) {
            $indexes = $this->discoverIndexes($table);

            foreach ($indexes as $index) {
                if (strtoupper($index['name']) === 'PRIMARY') {
                    continue;
                }

                try {
                    $this->dropIndex($table, $index['name']);
                } catch (\Throwable $e) {
                    // Suppress if already missing
                }

                $typeModifier = (isset($index['type']) && $index['type'] === 'FULLTEXT') ? 'FULLTEXT' : ($index['unique'] ? 'UNIQUE' : '');
                $binCols = array_map(fn($c) => "`{$c}`", $index['columns']);

                $sql = sprintf(
                    "ALTER TABLE `%s` ADD %s INDEX `%s` (%s)",
                    $table,
                    $typeModifier,
                    $index['name'],
                    implode(',', $binCols)
                );

                try {
                    $this->db->executeStatement($sql);
                    $output->writeln("  rebuilt index {$table}.{$index['name']}");
                } catch (\Throwable $e) {
                    // If a fulltext index or long key throws an error, log it but don't abort the migration
                    $output->writeln("  <comment>Skipped index {$table}.{$index['name']}: {$e->getMessage()}</comment>");
                }
            }
        }

        // 2. GUARANTEED SAFETY PASS: Forcibly index every migrated column explicitly
        $output->writeln('  <comment>Injecting fallback indexes for all migrated columns...</comment>');
        foreach ($uuidMap as $table => $columns) {
            foreach ($columns as $column) {
                $forcedIndexName = "idx_migrated_safety_" . strtolower($column);
                $sql = "ALTER TABLE `$table` ADD INDEX IF NOT EXISTS `$forcedIndexName` (`$column`)";

                try {
                    $this->db->executeStatement($sql);
                } catch (\Throwable $e) {
                    // Suppress if duplicate
                }
            }
        }

        $output->writeln('<fg=green>✓ Phase 9 complete</>');
    }

    /**
     * Step 9: Rebuild FKs
     *
     * @param array $foreignKeys
     * @param OutputInterface $output
     */
    private function rebuildForeignKeys(array $foreignKeys, OutputInterface $output): void
    {
        $output->writeln('<info>[Phase 10] Rebuild Foreign Keys</info>');

        $liveTables = $this->db->fetchFirstColumn("
            SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()
        ");
        $casedTableMap = [];
        foreach ($liveTables as $lt) {
            $casedTableMap[strtolower($lt)] = $lt;
        }

        $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($foreignKeys as $fk) {
                $rawTable = $fk['TABLE_NAME'] ?? $fk['table_name'] ?? '';
                $rawRefTable = $fk['REFERENCED_TABLE_NAME'] ?? $fk['referenced_table_name'] ?? '';

                // Normalize casing against live schema strings
                $table = $casedTableMap[strtolower($rawTable)] ?? $rawTable;
                $refTable = $casedTableMap[strtolower($rawRefTable)] ?? $rawRefTable;

                $this->createForeignKey(
                    $table,
                    $fk['columns'],
                    $refTable,
                    $fk['referenced_columns'],
                    $fk['constraint']
                );

                $output->writeln(sprintf('  recreated FK constraint %s on %s', $fk['constraint'], $table));
            }
        } finally {
            $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $output->writeln('<fg=green>✓ Phase 10 complete</>');
    }

    private function cleanupOldColumns(OutputInterface $output): int
    {
        $output->writeln('<info>[Cleanup] Dropping deprecated _old UUID columns</info>');

        $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // 1. Get a list of all tables that still have '_old' columns
            $oldColumns = $this->db->fetchAllAssociative("
                SELECT TABLE_NAME, COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND COLUMN_NAME LIKE '%\_old'
            ");

            // Group columns by table so we handle operations per-table
            $tablesToProcess = [];
            foreach ($oldColumns as $oc) {
                $tablesToProcess[$oc['TABLE_NAME']][] = $oc['COLUMN_NAME'];
            }

            foreach ($tablesToProcess as $table => $columns) {

                // 2. Check if this table has a multi-column key issue involving '_old' columns
                $hasLockedConstraints = (int) $this->db->fetchOne("
                    SELECT COUNT(*)
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME LIKE '%\_old'
                ", [$table]) > 0;

                if ($hasLockedConstraints) {
                    // Drop the active keys to unlock the columns
                    try {
                        $this->db->executeStatement("ALTER TABLE `$table` DROP PRIMARY KEY");
                    } catch (\Throwable $e) { /* Already gone */ }

                    try {
                        $this->db->executeStatement("ALTER TABLE `$table` DROP INDEX `uniq_page_tag_pair`");
                    } catch (\Throwable $e) { /* Already gone */ }

                    try {
                        $this->db->executeStatement("ALTER TABLE `$table` DROP INDEX `UNIQ_FA0E76BFA76ED395`");
                    } catch (\Throwable $e) { /* Already gone */ }
                }

                // 3. Drop all the _old columns for this table
                foreach ($columns as $column) {
                    try {
                        $this->db->executeStatement("ALTER TABLE `$table` DROP COLUMN `$column`");
                        $output->writeln("  ✓ Successfully dropped column {$table}.{$column}");
                    } catch (\Throwable $e) {
                        $output->writeln("  <error>Failed to drop column {$table}.{$column}: {$e->getMessage()}</error>");
                    }
                }

                // 4. RESTORATION STEP: Put the clean binary constraints back in place
                if ($hasLockedConstraints) {
                    try {
                        if (strtolower($table) === 'page_series') {
                            $this->db->executeStatement("ALTER TABLE `page_series` ADD PRIMARY KEY (`page_id`, `series_id`)");
                            $output->writeln("  ⚡ Restored clean PRIMARY KEY on page_series");
                        } elseif (strtolower($table) === 'series_pages') {
                            $this->db->executeStatement("ALTER TABLE `Series_pages` ADD PRIMARY KEY (`series_id`, `page_id`)");
                            $output->writeln("  ⚡ Restored clean PRIMARY KEY on Series_pages");
                        } elseif (strtolower($table) === 'page_categories') {
                            $this->db->executeStatement("ALTER TABLE `Page_categories` ADD PRIMARY KEY (`page_id`, `category_id`)");
                            $output->writeln("  ⚡ Restored clean PRIMARY KEY on Page_categories");
                        } elseif (strtolower($table) === 'page_tags') {
                            $this->db->executeStatement("ALTER TABLE `Page_tags` ADD PRIMARY KEY (`page_id`, `tag_id`)");
                            $this->db->executeStatement("ALTER TABLE `Page_tags` ADD UNIQUE INDEX `uniq_page_tag_pair` (`page_id`, `tag_id`)");
                            $output->writeln("  ⚡ Restored clean keys on Page_tags");
                        } elseif (strtolower($table) === 'user_preference') {
                            $this->db->executeStatement("ALTER TABLE `user_preference` ADD UNIQUE INDEX `UNIQ_FA0E76BFA76ED395` (`user_id`)");
                            $output->writeln("  ⚡ Restored clean unique index on user_preference");
                        }
                    } catch (\Throwable $e) {
                        $output->writeln("  <error>Failed to restore key on {$table}: {$e->getMessage()}</error>");
                    }
                }
            }

        } finally {
            $this->db->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $output->writeln("<fg=green>✓ Cleanup complete</>");

        return Command::SUCCESS;
    }
}
