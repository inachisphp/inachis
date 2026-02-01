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

final class SSLCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'db_ssl'; }
    public function getLabel(): string { return 'Database SSL/TLS'; }
    public function getSection(): string { return 'Database'; }

    public function run(): CheckResult
    {
        $platform = $this->connection->getDatabasePlatform();
        $platformName = $this->getDatabasePlatformName($platform);

        if (!in_array($platformName, ['mysql', 'mariadb'])) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'info',
                null,
                'SSL/TLS check only applies to MySQL/MariaDB.',
                null,
                $this->getSection(),
                'low'
            );
        }

        try {
            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'have_ssl'");
            $value = strtoupper($row['Value'] ?? 'NO');
            $status = $value === 'YES' ? 'ok' : 'warning';
            $severity = $status === 'ok' ? 'low' : 'high';
            $recommendation = $status === 'ok' ? null : 'Enable SSL/TLS for database connections to secure data in transit.';
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve SSL status: ' . $e->getMessage(),
                'Ensure database is running and credentials are correct.',
                $this->getSection(),
                'high'
            );
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $value,
            $recommendation,
            $this->getSection(),
            $severity
        );
    }
}