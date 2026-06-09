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

final class CharsetCollationCheck implements CheckInterface
{
    use DatabasePlatformTrait;

    /**
     * Constructor for the check
     *
     * @param Connection $connection
     */
    public function __construct(private readonly Connection $connection) {}

    /**
     * Gets the id of the check
     *
     * @return string
     */
    public function getId(): string { return 'db_charset_collation'; }

    /**
     * Gets the friendly name for the check
     *
     * @return string
     */
    public function getLabel(): string { return 'Character set / Collation'; }

    /**
     * Gets the name of the section this check appear sunder
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
        $platform = $this->connection->getDatabasePlatform();
        $platformName = $this->getDatabasePlatformName($platform);

        if (!in_array($platformName, ['mysql', 'mariadb'])) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'info',
                null,
                'Charset check only applies to MySQL/MariaDB.',
                null,
                $this->getSection(),
                'low'
            );
        }

        try {
            /** @var array<string, string> */
            $row = $this->connection->fetchAssociative(
                "SELECT @@character_set_database AS charset, @@collation_database AS collation"
            );

            $charset = $row['charset'] ?? '';
            $collation = $row['collation'] ?? '';

            $status = ($charset === 'utf8mb4' && str_starts_with($collation, 'utf8mb4')) ? 'ok' : 'warning';
            $severity = $status === 'ok' ? 'low' : 'medium';
            $value = "Charset: $charset, Collation: $collation";
            $recommendation = $status === 'ok' ? null : 'Use utf8mb4 character set and compatible collation for modern apps.';
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve charset/collation: ' . $e->getMessage(),
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