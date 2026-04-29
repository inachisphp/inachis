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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE analytics_page_view');
        $this->addSql('DROP TABLE analytics_unique_visitor');
    }
}