<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics tracking tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE analytics_page_view (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                views INTEGER NOT NULL DEFAULT 0
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uniq_path_date
            ON analytics_page_view (path, date)
        ');

        $this->addSql('
            CREATE TABLE analytics_unique_visitor (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                visitor_hash CHAR(64) NOT NULL,
                date DATE NOT NULL
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uniq_visitor_date
            ON analytics_unique_visitor (visitor_hash, date)
        ');

        $this->addSql('
            CREATE TABLE analytics_errors (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                code INTEGER NOT NULL DEFAULT 0,
                hits INTEGER NOT NULL DEFAULT 0
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uniq_path_date_code
            ON analytics_errors (path, date, code)
        ');

        $this->addSql('
            CREATE TABLE analytics_referrer (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                domain VARCHAR(255) NOT NULL,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                hits INTEGER NOT NULL DEFAULT 0
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uniq_domain_path_date
            ON analytics_referrer (domain, path, date)
        ');

        $this->addSql('
            CREATE INDEX idx_analytics_referrer_domain
            ON analytics_referrer (domain)
        ');

        $this->addSql('
            CREATE INDEX idx_analytics_page_view_date
            ON analytics_page_view (date)
        ');

        $this->addSql('
            CREATE INDEX idx_analytics_unique_visitor_date
            ON analytics_unique_visitor (date)
        ');

        $this->addSql('
            CREATE INDEX idx_analytics_errors_date
            ON analytics_errors (date)
        ');

        $this->addSql('
            CREATE INDEX idx_analytics_errors_code
            ON analytics_errors (code)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_analytics_page_view_date ON analytics_page_view');
        $this->addSql('DROP INDEX idx_analytics_unique_visitor_date ON analytics_unique_visitor');
        $this->addSql('DROP INDEX idx_analytics_errors_date ON analytics_errors');
        $this->addSql('DROP INDEX idx_analytics_errors_code ON analytics_errors');
        $this->addSql('DROP INDEX idx_analytics_referrer_domain ON analytics_referrer');
        $this->addSql('DROP TABLE analytics_page_view');
        $this->addSql('DROP TABLE analytics_unique_visitor');
        $this->addSql('DROP TABLE analytics_errors');
        $this->addSql('DROP TABLE analytics_referrer');
    }
}
