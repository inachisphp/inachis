<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Safely renames visibility and timestamp columns across multiple tables.
 */
final class Version20260629082534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename visibility and legacy date columns to standard visible, created_at, and updated_at naming conventions.';
    }

    public function up(Schema $schema): void
    {
        // Series table updates
        $this->addSql('ALTER TABLE series CHANGE visibility visible TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE series CHANGE mod_date updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE series CHANGE create_date created_at DATETIME DEFAULT NULL');

        // Page table updates
        $this->addSql('ALTER TABLE page CHANGE visibility visible TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE page CHANGE create_date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE page CHANGE mod_date updated_at DATETIME NOT NULL');

        // Revision table updates
        $this->addSql('ALTER TABLE revision CHANGE mod_date created_at DATETIME NOT NULL');

        // User table updates
        $this->addSql('ALTER TABLE user CHANGE create_date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE mod_date updated_at DATETIME NOT NULL');

        // Download table updates
        $this->addSql('ALTER TABLE download CHANGE create_date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE download CHANGE mod_date updated_at DATETIME NOT NULL');

        // Image table updates
        $this->addSql('ALTER TABLE image CHANGE create_date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE image CHANGE mod_date updated_at DATETIME NOT NULL');

        // Url table updates
        $this->addSql('ALTER TABLE url CHANGE create_date created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE url CHANGE mod_date updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert Series table
        $this->addSql('ALTER TABLE series CHANGE visible visibility TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE series CHANGE updated_at mod_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE series CHANGE created_at create_date DATETIME DEFAULT NULL');

        // Revert Page table
        $this->addSql('ALTER TABLE page CHANGE visible visibility TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE page CHANGE created_at create_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE page CHANGE updated_at mod_date DATETIME NOT NULL');

        // Revert Revision table
        $this->addSql('ALTER TABLE revision CHANGE created_at mod_date DATETIME NOT NULL');

        // Revert User table
        $this->addSql('ALTER TABLE user CHANGE created_at create_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE updated_at mod_date DATETIME NOT NULL');

        // Revert Download table
        $this->addSql('ALTER TABLE download CHANGE created_at create_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE download CHANGE updated_at mod_date DATETIME NOT NULL');

        // Revert Image table
        $this->addSql('ALTER TABLE image CHANGE created_at create_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE image CHANGE updated_at mod_date DATETIME NOT NULL');

        // Revert Url table
        $this->addSql('ALTER TABLE url CHANGE created_at create_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE url CHANGE updated_at mod_date DATETIME NOT NULL');
    }
}
