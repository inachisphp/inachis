<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Inachis\Doctrine\DatabasePlatformTrait;

final class DatabasePerformanceCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    /**
     * Constructor
     *
     * @param Connection $connection The connection to use for the check
     */
    public function __construct(private readonly Connection $connection) {}

    /**
     * Returns the ID of the check
     *
     * @return string
     */
    public function getId(): string { return 'database_health'; }

    /**
     * Returns the label of the check
     *
     * @return string
     */
    public function getLabel(): string { return 'Database Health'; }

    /**
     * Returns the section of the check
     *
     * @return string
     */
    public function getSection(): string { return 'Database'; }

    /**
     * Runs the check
     *
     * @return CheckResult
     */
    public function run(): CheckResult
    {
        try {
            $platform = $this->connection->getDatabasePlatform();

            if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
                return $this->checkMySql();
            }

            if ($platform instanceof PostgreSQLPlatform) {
                return $this->checkPostgres();
            }

            return $this->unsupported($this->getDatabasePlatformName($platform));

        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Checks the performance of a MySQL database
     *
     * @return CheckResult
     */
    private function checkMySql(): CheckResult
    {
        $issues = [];
        $severity = 'ok';

        /** @var array{Variable_name: string, Value: numeric-string} */
        $row = $this->connection->fetchAssociative("SHOW STATUS LIKE 'Threads_running'");
        $threads = (int) $row['Value'];
            
        /** @var array{Variable_name: string, Value: numeric-string} */
        $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'max_connections'");
        $maxConnections = (int) $row['Value'];

        $usage = $maxConnections > 0
            ? round(($threads / $maxConnections) * 100, 1)
            : 0;

        if ($usage > 80) {
            $severity = 'error';
            $issues[] = "High connection usage ({$usage}%)";
        } elseif ($usage > 60) {
            $severity = 'warning';
            $issues[] = "Elevated connection usage ({$usage}%)";
        }

        /** @var array{Variable_name: string, Value: numeric-string} */
        $row = $this->connection->fetchAssociative(
            "SELECT COUNT(*) AS Value FROM information_schema.processlist WHERE TIME > 10 AND COMMAND != 'Sleep'"
        );
        $longQueries = (int) $row['Value'];

        if ($longQueries > 5) {
            $severity = 'error';
            $issues[] = "{$longQueries} long-running queries";
        } elseif ($longQueries > 0) {
            $severity = $severity === 'error' ? 'error' : 'warning';
            $issues[] = "{$longQueries} long-running queries";
        }

        /** @var array{Variable_name: string, Value: numeric-string} */
        $row = $this->connection->fetchAssociative("SHOW STATUS LIKE 'Innodb_deadlocks'");
        $deadlocks = (int) $row['Value'];

        if ($deadlocks > 0) {
            $severity = 'warning';
            $issues[] = "{$deadlocks} InnoDB deadlocks detected";
        }

        /** @var array{Variable_name: string, Value: numeric-string} */
        $row = $this->connection->fetchAssociative("SHOW STATUS LIKE 'Innodb_row_lock_current_waits'");
        $lockWaits = (int) $row['Value'];

        if ($lockWaits > 0) {
            $severity = 'warning';
            $issues[] = "{$lockWaits} row lock waits";
        }

        try {
            /** @var array{Seconds_Behind_Master?: numeric-string}|false */
            $replica = $this->connection->fetchAssociative('SHOW SLAVE STATUS');
            if ($replica !== false && isset($replica['Seconds_Behind_Master'])) {
                $lag = (int) $replica['Seconds_Behind_Master'];
                if ($lag > 30) {
                    $severity = 'error';
                    $issues[] = "Replication lag: {$lag}s";
                } elseif ($lag > 5) {
                    $severity = 'warning';
                    $issues[] = "Replication lag: {$lag}s";
                }
            }
        } catch (\Throwable) {
            // not a replica or no permission
        }

        $summary = $issues === []
            ? 'Healthy'
            : implode('; ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $severity,
            $usage . '%',
            $summary,
            $severity === 'ok' ? null : 'Database experiencing stress conditions.',
            $this->getSection(),
            'high'
        );
    }

    /**
     * Checks the performance of a PostgreSQL database
     *
     * @return CheckResult
     */
    private function checkPostgres(): CheckResult
    {
        $issues = [];
        $severity = 'ok';

        /** @var numeric-string|int $value */
        $value = $this->connection->fetchOne(
            "SELECT count(*) FROM pg_stat_activity WHERE state != 'idle'"
        );
        $active = (int) $value;

        /** @var numeric-string|int $value */
        $value = $this->connection->fetchOne(
            "SHOW max_connections"
        );
        $maxConnections = (int) $value;

        $usage = $maxConnections > 0
            ? round(($active / $maxConnections) * 100, 1)
            : 0;

        if ($usage > 80) {
            $severity = 'error';
            $issues[] = "High connection usage ({$usage}%)";
        } elseif ($usage > 60) {
            $severity = 'warning';
            $issues[] = "Elevated connection usage ({$usage}%)";
        }

        /** @var numeric-string|int $value */
        $value = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM pg_stat_activity WHERE state = 'active' AND now() - query_start > interval '10 seconds'"
        );
        $longQueries = (int) $value;

        if ($longQueries > 5) {
            $severity = 'error';
            $issues[] = "{$longQueries} long-running queries";
        } elseif ($longQueries > 0) {
            $severity = $severity === 'error' ? 'error' : 'warning';
            $issues[] = "{$longQueries} long-running queries";
        }

        /** @var numeric-string|int $value */
        $value = $this->connection->fetchOne(
            "SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()"
        );
        $deadlocks = (int) $value;

        if ($deadlocks > 0) {
            $severity = 'warning';
            $issues[] = "{$deadlocks} deadlocks detected";
        }

        try {
            /** @var numeric-string|int|null */
            $lag = $this->connection->fetchOne(
                "SELECT EXTRACT(EPOCH FROM now() - pg_last_xact_replay_timestamp())"
            );

            if ($lag !== null) {
                $lag = (int) $lag;
                if ($lag > 30) {
                    $severity = 'error';
                    $issues[] = "Replication lag: {$lag}s";
                } elseif ($lag > 5) {
                    $severity = 'warning';
                    $issues[] = "Replication lag: {$lag}s";
                }
            }
        } catch (\Throwable) {
            // not replica
        }

        $summary = $issues === []
            ? 'Healthy'
            : implode('; ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $severity,
            $usage . '%',
            $summary,
            $severity === 'ok' ? null : 'Database experiencing stress conditions.',
            $this->getSection(),
            'high'
        );
    }

    /**
     * Returns a warning check result
     *
     * @param string $platform The platform name
     * @return CheckResult
     */
    private function unsupported(string $platform): CheckResult
    {
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            'warning',
            'Unsupported',
            "Platform '{$platform}' not supported.",
            null,
            $this->getSection(),
            'medium'
        );
    }

    /**
     * Returns a failed check result
     *
     * @param string $message The error message
     * @return CheckResult
     */
    private function error(string $message): CheckResult
    {
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            'error',
            'Query failed',
            $message,
            'Verify DB permissions.',
            $this->getSection(),
            'high'
        );
    }
}
