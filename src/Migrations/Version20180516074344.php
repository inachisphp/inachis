<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180516074344 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE series (id VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, first_date DATETIME NOT NULL, last_date DATETIME NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Series_pages (series_id VARCHAR(255) NOT NULL, page_id VARCHAR(255) NOT NULL, INDEX IDX_70B3265B514956FD (series_id), INDEX IDX_70B3265BC4663E4 (page_id), PRIMARY KEY(series_id, page_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Series_pages ADD CONSTRAINT FK_70B3265B514956FD FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE Series_pages ADD CONSTRAINT FK_70B3265BC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Series_pages DROP FOREIGN KEY FK_70B3265B514956FD');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE Series_pages');
    }
}
