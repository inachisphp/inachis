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

final class TableEngineCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'db_table_engine'; }
    public function getLabel(): string { return 'Table engine'; }
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
                'Table engine check only applies to MySQL/MariaDB.',
                null,
                $this->getSection(),
                'low'
            );
        }

        try {
            $tables = $this->connection->fetchAllAssociative("SHOW TABLE STATUS");
            $nonInnoDB = array_filter($tables, fn($t) => strtoupper($t['Engine'] ?? '') !== 'INNODB');
            $count = count($nonInnoDB);
            $status = $count === 0 ? 'ok' : 'warning';
            $severity = $count === 0 ? 'low' : 'medium';
            $value = $count === 0 ? 'All tables are InnoDB' : "$count non-InnoDB table(s) found";
            $recommendation = $count === 0 ? null : 'Consider converting tables to InnoDB for performance and reliability.';
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve table status: ' . $e->getMessage(),
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