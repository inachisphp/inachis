<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Database;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Inachis\Doctrine\DatabasePlatformTrait;
use Doctrine\DBAL\Connection;

final class SlowQueryLogCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'slow_query_log'; }
    public function getLabel(): string { return 'slow_query_log'; }
    public function getSection(): string { return 'Database'; }

    public function run(): CheckResult
    {
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platformName = $this->getDatabasePlatformName($platform);

            if (!in_array($platformName, ['mysql', 'mariadb'])) {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'info',
                    null,
                    'Slow query log check only applies to MySQL/MariaDB.',
                    null,
                    $this->getSection(),
                    'low'
                );
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'slow_query_log'");
            $value = ($row['Value'] ?? 'OFF') === 'ON' ? 'enabled' : 'disabled';
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve slow_query_log: ' . $e->getMessage(),
                'Ensure database is running and credentials are correct.',
                $this->getSection(),
                'high'
            );
        }

        $status = $value === 'enabled' ? 'ok' : 'info';
        $severity = 'low';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok'
                ? 'Slow query log is enabled.'
                : 'Slow query log is disabled; enable for monitoring.',
            $status !== 'ok'
                ? 'Enable slow_query_log to detect slow queries and optimize them.'
                : null,
            $this->getSection(),
            $severity
        );
    }
}