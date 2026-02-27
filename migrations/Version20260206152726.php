<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Insert default security policies if they do not exist
 */
final class Version20260206152726 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Insert default security policies if they do not exist';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Default Policy
        $this->addSql("
            INSERT INTO security_policy 
                (id, name, min_length, require_uppercase, require_lowercase, require_number, require_special, password_history, max_failed_login_attempts, lockout_duration_minutes, password_expiry_days, admin_require_2fa, super_admin_require_2fa, super_admin_requires_webauthn, step_up_for_sensitive_actions, created_at, updated_at, is_read_only, is_active)
            SELECT UUID(), 'Custom', 8, 1, 1, 1, 1, 0, 10, 15, 0, 0, 0, 0, 1, NOW(), NOW(), 0, 1
            WHERE NOT EXISTS (SELECT 1 FROM security_policy WHERE name='Custom')
        ");

        // Basic Policy
        $this->addSql("
            INSERT INTO security_policy 
                (id, name, min_length, require_uppercase, require_lowercase, require_number, require_special, password_history, max_failed_login_attempts, lockout_duration_minutes, password_expiry_days, admin_require_2fa, super_admin_require_2fa, super_admin_requires_webauthn, step_up_for_sensitive_actions, created_at, updated_at, is_read_only, is_active)
            SELECT UUID(), 'Basic', 8, 1, 1, 1, 1, 0, 10, 15, 0, 0, 0, 0, 1, NOW(), NOW(), 0, 1
            WHERE NOT EXISTS (SELECT 1 FROM security_policy WHERE name='Basic')
        ");

        // Strict Policy
        $this->addSql("
            INSERT INTO security_policy 
                (id, name, min_length, require_uppercase, require_lowercase, require_number, require_special, password_history, max_failed_login_attempts, lockout_duration_minutes, password_expiry_days, admin_require_2fa, super_admin_require_2fa, super_admin_requires_webauthn, step_up_for_sensitive_actions, created_at, updated_at, is_read_only, is_active)
            SELECT UUID(), 'Strict', 16, 1, 1, 1, 1, 10, 5, 60, 60, 1, 1, 1, 1, NOW(), NOW(), 1, 0
            WHERE NOT EXISTS (SELECT 1 FROM security_policy WHERE name='Strict')
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM security_policy WHERE name IN ('Custom', 'Basic', 'Strict')");
    }
}
