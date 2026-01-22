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

final class MaxConnectionsCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'db_max_connections'; }
    public function getLabel(): string { return 'max_connections / max worker threads'; }
    public function getSection(): string { return 'Database'; }

    public function run(): CheckResult
    {
        try {
            $platform = $this->connection->getDatabasePlatform();
            $platformName = $this->getDatabasePlatformName($platform);
            if (in_array($platformName, ['mysql', 'mariadb'])) {
                $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'max_connections'");
                $value = (int) ($row['Value'] ?? 0);
                $recommended = 100;
            } elseif ($platformName === 'sqlserver') {
                $row = $this->connection->fetchAssociative(
                    "SELECT value_in_use FROM sys.configurations WHERE name = 'max worker threads'"
                );
                $value = (int) ($row['value_in_use'] ?? 0);
                $recommended = 256;
            } else {
                $severity = 'low';
                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'info',
                    null,
                    'Database platform not supported for this check.',
                    null,
                    $this->getSection(),
                    ''
                );
            }
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not connect to database: ' . $e->getMessage(),
                'Check database credentials and availability.',
                $this->getSection(),
                'high'
            );
        }

        $status = $value < $recommended ? 'warning' : 'ok';
        $severity = $value < $recommended ? 'medium' : 'low';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok'
                ? "Max connections/workers ($value) is sufficient."
                : "Max connections/workers ($value) below recommended ($recommended).",
            $status !== 'ok'
                ? "Increase max connections / worker threads according to your DB platform recommendations."
                : null,
            $this->getSection(),
            '',
        );
    }
}