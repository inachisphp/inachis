<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Database;

use Doctrine\DBAL\Connection;
use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Inachis\Doctrine\DatabasePlatformTrait;

final class SQLServerMemoryCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection)
    {
    }

    public function getId(): string
    {
        return 'sqlsrv_max_memory';
    }

    public function getLabel(): string
    {
        return 'max server memory';
    }

    public function getSection(): string
    {
        return 'Database';
    }

    public function run(): CheckResult
    {
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platformName = $this->getDatabasePlatformName($platform);

            if ('sqlserver' !== $platformName) {
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'info',
                    null,
                    'Max server memory check only applies to SQL Server.',
                    null,
                    $this->getSection(),
                    'low',
                );
            }

            /** @var array{value_in_use: int}|false */
            $row = $this->connection->fetchAssociative(
                "SELECT value_in_use FROM sys.configurations WHERE name = 'max server memory (MB)'",
            );
            $value = (int) ($row['value_in_use'] ?? 0);
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not connect to SQL Server: '.$e->getMessage(),
                'Check database credentials and availability.',
                $this->getSection(),
                'high',
            );
        }

        $recommended = 2048;
        $status = $value >= $recommended ? 'ok' : 'warning';
        $severity = $value >= $recommended ? 'low' : 'medium';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value.' MB',
            'ok' === $status
                ? 'Max server memory is sufficient.'
                : "Max server memory ($value MB) below recommended ($recommended MB).",
            'ok' !== $status
                ? 'Increase max server memory in SQL Server configuration.'
                : null,
            $this->getSection(),
            $severity,
        );
    }
}
