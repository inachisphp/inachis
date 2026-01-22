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

final class SQLServerMemoryCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'sqlsrv_max_memory'; }
    public function getLabel(): string { return 'max server memory'; }
    public function getSection(): string { return 'Database'; }

    public function run(): CheckResult
    {
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platformName = $this->getDatabasePlatformName($platform);

            if ($platformName !== 'sqlserver') {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'info',
                    null,
                    'Max server memory check only applies to SQL Server.',
                    null,
                    $this->getSection(),
                    'low'
                );
            }

            $row = $this->connection->fetchAssociative(
                "SELECT value_in_use FROM sys.configurations WHERE name = 'max server memory (MB)'"
            );
            $value = (int) ($row['value_in_use'] ?? 0);
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not connect to SQL Server: ' . $e->getMessage(),
                'Check database credentials and availability.',
                $this->getSection(),
                'high'
            );
        }

        $recommended = 2048;
        $status = $value >= $recommended ? 'ok' : 'warning';
        $severity = $value >= $recommended ? 'low' : 'medium';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value . ' MB',
            $status === 'ok'
                ? 'Max server memory is sufficient.'
                : "Max server memory ($value MB) below recommended ($recommended MB).",
            $status !== 'ok'
                ? 'Increase max server memory in SQL Server configuration.'
                : null,
            $this->getSection(),
            $severity
        );
    }
}