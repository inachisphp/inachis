<?php

declare(strict_types=1);

namespace Inachis\Service\System;

use Doctrine\DBAL\Connection;

class DatabasePurgeService
{
    public const EXCLUDED_TABLES = [
        'doctrine_migration_versions',
        'login_activity',
        'password_reset_requests',
        'role',
        'role_permission',
        'security_policy',
        'setting',
        'user',
        'user_passkeys',
        'user_preference',
        'user_recovery_code',
        'user_roles',
        'user_totp',
        'user_trusted_device',
        'user_view_state',
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Truncates all user tables while preserving schema.
     * 
     * @return list<string>
     */
    public function purgeUserTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        $purgedTables = [];

        $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');

        try {
            foreach ($tables as $table) {
                if (in_array($table, self::EXCLUDED_TABLES, true)) {
                    continue;
                }

                $quotedTable = $this->connection->quoteIdentifier($table);
                $this->connection->executeStatement(sprintf('TRUNCATE TABLE %s', $quotedTable));
                $purgedTables[] = $table;
            }
        } finally {
            $this->connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        return $purgedTables;
    }
}
