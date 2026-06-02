<?php

final class Version20260429120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics_page_view and analytics_unique_visitor tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE analytics_page_view (
                id BIGINT AUTO_INCREMENT NOT NULL,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                views INT NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_path_date (path, date),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE analytics_unique_visitor (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                visitor_hash CHAR(64) NOT NULL,
                date DATE NOT NULL,
                UNIQUE KEY uniq_visitor_date (visitor_hash, date)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE analytics_errors (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                code INT NOT NULL DEFAULT 0,
                hits INT NOT NULL DEFAULT 0,
                UNIQUE KEY uniq_path_date_code (path, date, code)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
        $this->addSql('
            CREATE TABLE analytics_referrer (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                domain VARCHAR(255) NOT NULL,
                path VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                hits INT NOT NULL DEFAULT 0,
                UNIQUE KEY uniq_domain_path_date (domain, path, date),
                INDEX idx_domain (domain)
            );
        ');
        $this->addSql('CREATE INDEX idx_analytics_page_view_date ON analytics_page_view (date)');
        $this->addSql('CREATE INDEX idx_analytics_unique_visitor_date ON analytics_unique_visitor (date)');
        $this->addSql('CREATE INDEX idx_analytics_errors_date ON analytics_errors (date)');
        $this->addSql('CREATE INDEX idx_analytics_errors_code ON analytics_errors (code)');
        $this->addSql('CREATE INDEX idx_analytics_referrer_domain ON analytics_referrer (domain)');
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