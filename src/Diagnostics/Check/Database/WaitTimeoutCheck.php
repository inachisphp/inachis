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

final class WaitTimeoutCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'wait_timeout'; }
    public function getLabel(): string { return 'wait_timeout'; }
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
                    'wait_timeout only applies to MySQL/MariaDB.',
                    null,
                    $this->getSection(),
                    'low'
                );
            }

            $row = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'wait_timeout'");
            $value = (int) ($row['Value'] ?? 0);
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve wait_timeout: ' . $e->getMessage(),
                'Ensure database is running and credentials are correct.',
                $this->getSection(),
                'high'
            );
        }

        $recommended = 60;
        $status = $value >= $recommended ? 'ok' : 'warning';
        $severity = $value >= $recommended ? 'low' : 'medium';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value . ' seconds',
            $status === 'ok'
                ? 'wait_timeout is acceptable.'
                : 'wait_timeout is low; may drop idle connections too quickly.',
            $status !== 'ok'
                ? "Increase wait_timeout to >= $recommended for stable connections."
                : null,
            $this->getSection(),
            $severity
        );
    }
}