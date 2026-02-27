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
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class DatabasePerformanceCheck implements CheckInterface
{
    /**
     * Constructor
     *
     * @param Connection $connection The connection to use for the check
     */
    public function __construct(
        private readonly Connection $connection,
    ) {}

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

            if ($platform instanceof MySQLPlatform) {
                return $this->checkMySql();
            }

            if ($platform instanceof PostgreSQLPlatform) {
                return $this->checkPostgres();
            }

            return $this->unsupported($platform);

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

        $threads = (int) $this->connection
            ->fetchOne("SHOW STATUS LIKE 'Threads_running'", [], [], 1);

        $maxConnections = (int) $this->connection
            ->fetchOne("SHOW VARIABLES LIKE 'max_connections'", [], [], 1);

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

        $longQueries = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.processlist WHERE TIME > 10 AND COMMAND != 'Sleep'"
        );

        if ($longQueries > 5) {
            $severity = 'error';
            $issues[] = "{$longQueries} long-running queries";
        } elseif ($longQueries > 0) {
            $severity = $severity === 'error' ? 'error' : 'warning';
            $issues[] = "{$longQueries} long-running queries";
        }

        $deadlocks = (int) $this->connection
            ->fetchOne("SHOW STATUS LIKE 'Innodb_deadlocks'", [], [], 1);

        if ($deadlocks > 0) {
            $severity = 'warning';
            $issues[] = "{$deadlocks} InnoDB deadlocks detected";
        }

        $lockWaits = (int) $this->connection
            ->fetchOne("SHOW STATUS LIKE 'Innodb_row_lock_current_waits'", [], [], 1);

        if ($lockWaits > 0) {
            $severity = 'warning';
            $issues[] = "{$lockWaits} row lock waits";
        }

        try {
            $replica = $this->connection->fetchAssociative("SHOW SLAVE STATUS");
            if ($replica && isset($replica['Seconds_Behind_Master'])) {
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

        $active = (int) $this->connection->fetchOne(
            "SELECT count(*) FROM pg_stat_activity WHERE state != 'idle'"
        );

        $maxConnections = (int) $this->connection->fetchOne(
            "SHOW max_connections"
        );

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

        $longQueries = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM pg_stat_activity WHERE state = 'active' AND now() - query_start > interval '10 seconds'"
        );

        if ($longQueries > 5) {
            $severity = 'error';
            $issues[] = "{$longQueries} long-running queries";
        } elseif ($longQueries > 0) {
            $severity = $severity === 'error' ? 'error' : 'warning';
            $issues[] = "{$longQueries} long-running queries";
        }

        $deadlocks = (int) $this->connection->fetchOne(
            "SELECT deadlocks FROM pg_stat_database WHERE datname = current_database()"
        );

        if ($deadlocks > 0) {
            $severity = 'warning';
            $issues[] = "{$deadlocks} deadlocks detected";
        }

        try {
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
