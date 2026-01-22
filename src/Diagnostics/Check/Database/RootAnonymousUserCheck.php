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

final class RootAnonymousUserCheck implements CheckInterface
{
    use DatabasePlatformTrait;
    
    public function __construct(private readonly Connection $connection) {}

    public function getId(): string { return 'db_root_anonymous_users'; }
    public function getLabel(): string { return 'Root / anonymous users'; }
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
                'User checks only apply to MySQL/MariaDB.',
                null,
                $this->getSection(),
                'low'
            );
        }

        try {
            $users = $this->connection->fetchAllAssociative(
                "SELECT User, Host FROM mysql.user WHERE User = '' OR User = 'root'"
            );

            $warnings = [];
            foreach ($users as $user) {
                if ($user['User'] === '') {
                    $warnings[] = "Anonymous user at host {$user['Host']}";
                } elseif ($user['User'] === 'root' && $user['Host'] !== 'localhost') {
                    $warnings[] = "Root user with remote access ({$user['Host']})";
                }
            }

            $status = count($warnings) === 0 ? 'ok' : 'warning';
            $severity = count($warnings) === 0 ? 'low' : 'high';
            $value = count($warnings) === 0 ? 'No insecure users found' : implode('; ', $warnings);
            $recommendation = $status === 'ok' ? null : 'Remove anonymous users and restrict root access to localhost only.';
        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                null,
                'Could not retrieve MySQL users: ' . $e->getMessage(),
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