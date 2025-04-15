<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180314150126 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create the initial schema';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE category (id VARCHAR(255) NOT NULL, parent_id VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), INDEX search_idx (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id VARCHAR(255) NOT NULL, title VARCHAR(50) NOT NULL, INDEX search_idx (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id VARCHAR(255) NOT NULL, username VARCHAR(512) NOT NULL, usernameCanonical VARCHAR(255) NOT NULL, password VARCHAR(512) NOT NULL, email VARCHAR(512) NOT NULL, emailCanonical VARCHAR(255) NOT NULL, display_name VARCHAR(512) NOT NULL, is_active TINYINT(1) NOT NULL, is_removed TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, password_mod_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649F5A5DC32 (usernameCanonical), UNIQUE INDEX UNIQ_8D93D649885281E (emailCanonical), INDEX search_idx (usernameCanonical, emailCanonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE url (id VARCHAR(255) NOT NULL, content_id VARCHAR(255) DEFAULT NULL, link VARCHAR(512) NOT NULL, linkCanonical VARCHAR(255) NOT NULL, defaultLink TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_F47645AECE49579E (linkCanonical), INDEX IDX_F47645AE84A0A3ED (content_id), INDEX search_idx (linkCanonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page (id VARCHAR(255) NOT NULL, author_id VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, feature_image VARCHAR(255) DEFAULT NULL, feature_snippet LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, visibility VARCHAR(20) NOT NULL, create_date DATETIME NOT NULL, post_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, timezone VARCHAR(50) NOT NULL, password VARCHAR(255) DEFAULT NULL, allow_comments TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, latlong VARCHAR(255) DEFAULT NULL, sharing_message VARCHAR(140) DEFAULT NULL, INDEX IDX_140AB620F675F31B (author_id), INDEX search_idx (title, author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Page_categories (page_id VARCHAR(255) NOT NULL, category_id VARCHAR(255) NOT NULL, INDEX IDX_B9D64968C4663E4 (page_id), INDEX IDX_B9D6496812469DE2 (category_id), PRIMARY KEY(page_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Page_tags (page_id VARCHAR(255) NOT NULL, tag_id VARCHAR(255) NOT NULL, INDEX IDX_6A890A5FC4663E4 (page_id), INDEX IDX_6A890A5FBAD26311 (tag_id), PRIMARY KEY(page_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id VARCHAR(255) NOT NULL, dimension_x INT NOT NULL, dimension_y INT NOT NULL, caption VARCHAR(255) NOT NULL, alt_text VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, filename VARCHAR(255) NOT NULL, filetype VARCHAR(255) NOT NULL, filesize INT NOT NULL, checksum VARCHAR(255) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, INDEX search_idx (title, filename, filetype), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE url ADD CONSTRAINT FK_F47645AE84A0A3ED FOREIGN KEY (content_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE Page_categories ADD CONSTRAINT FK_B9D64968C4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE Page_categories ADD CONSTRAINT FK_B9D6496812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE Page_tags ADD CONSTRAINT FK_6A890A5FC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE Page_tags ADD CONSTRAINT FK_6A890A5FBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE Page_categories DROP FOREIGN KEY FK_B9D6496812469DE2');
        $this->addSql('ALTER TABLE Page_tags DROP FOREIGN KEY FK_6A890A5FBAD26311');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620F675F31B');
        $this->addSql('ALTER TABLE url DROP FOREIGN KEY FK_F47645AE84A0A3ED');
        $this->addSql('ALTER TABLE Page_categories DROP FOREIGN KEY FK_B9D64968C4663E4');
        $this->addSql('ALTER TABLE Page_tags DROP FOREIGN KEY FK_6A890A5FC4663E4');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE url');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE Page_categories');
        $this->addSql('DROP TABLE Page_tags');
        $this->addSql('DROP TABLE image');
    }
}
