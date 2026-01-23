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
use Inachis\Util\NumberFormatter;

final class InnoDBBufferPoolCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'innodb_buffer_pool_size'; }
    public function getLabel(): string { return 'innodb_buffer_pool_size'; }
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
                    'Buffer pool check only applies to MySQL/MariaDB.',
                    null,
                    $this->getSection(),
                    'low'
                );
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
            $value = (int) ($row['Value'] ?? 0);
            $recommended = 536870912; // 512 MB
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve innodb_buffer_pool_size: ' . $e->getMessage(),
                'Ensure database is running and credentials are correct.',
                $this->getSection(),
                'high'
            );
        }

        $status = $value >= $recommended ? 'ok' : 'warning';
        $severity = $value >= $recommended ? 'low' : 'medium';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            NumberFormatter::formatBytes($value),
            $status === 'ok'
                ? 'InnoDB buffer pool size is sufficient.'
                : 'InnoDB buffer pool is smaller than recommended; may affect performance.',
            $status !== 'ok'
                ? "Consider increasing innodb_buffer_pool_size to 50–70% of available RAM for dedicated DB servers, and 20-40% for shared servers."
                : null,
            $this->getSection(),
            $severity
        );
    }
}