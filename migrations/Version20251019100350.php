<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019100350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', parent_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, visible TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), INDEX search_idx (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', dimension_x INT NOT NULL, dimension_y INT NOT NULL, alt_text VARCHAR(255) DEFAULT NULL, title VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, filename VARCHAR(255) NOT NULL, filetype VARCHAR(255) NOT NULL, filesize INT NOT NULL, checksum VARCHAR(255) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, INDEX search_idx (title, filename, filetype), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE login_activity (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', username VARCHAR(512) NOT NULL, remote_ip VARCHAR(50) NOT NULL, user_agent VARCHAR(256) NOT NULL, attempt_count INT NOT NULL, timestamp DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', author_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', image_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, feature_snippet LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, visibility TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, post_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, timezone VARCHAR(50) NOT NULL, password VARCHAR(255) DEFAULT NULL, allow_comments TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, latlong VARCHAR(255) DEFAULT NULL, sharing_message VARCHAR(140) DEFAULT NULL, language VARCHAR(15) DEFAULT NULL, INDEX IDX_140AB620F675F31B (author_id), INDEX IDX_140AB6203DA5256D (image_id), INDEX search_idx (title, author_id, image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Page_categories (page_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', category_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_B9D64968C4663E4 (page_id), INDEX IDX_B9D6496812469DE2 (category_id), PRIMARY KEY(page_id, category_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Page_tags (page_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', tag_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_6A890A5FC4663E4 (page_id), INDEX IDX_6A890A5FBAD26311 (tag_id), PRIMARY KEY(page_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page_series (page_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', series_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_55C6F733C4663E4 (page_id), INDEX IDX_55C6F7335278319C (series_id), PRIMARY KEY(page_id, series_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE password_reset_requests (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', token_hash VARCHAR(128) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', used TINYINT(1) NOT NULL, INDEX IDX_9075A748A76ED395 (user_id), INDEX search_idx (user_id, token_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE revision (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', page_id VARCHAR(255) NOT NULL, version_number INT NOT NULL, action VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, mod_date DATETIME NOT NULL, INDEX IDX_6D6315CCA76ED395 (user_id), INDEX search_idx (page_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', image_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, sub_title VARCHAR(255) DEFAULT NULL, url VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, first_date DATETIME DEFAULT NULL, last_date DATETIME DEFAULT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, visibility TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_3A10012DF47645AE (url), INDEX IDX_3A10012D3DA5256D (image_id), INDEX search_idx (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Series_pages (series_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', page_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_4DCADA905278319C (series_id), INDEX IDX_4DCADA90C4663E4 (page_id), PRIMARY KEY(series_id, page_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(50) NOT NULL, INDEX search_idx (title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE url (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', content_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', link VARCHAR(512) NOT NULL, linkCanonical VARCHAR(255) NOT NULL, defaultLink TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, UNIQUE INDEX UNIQ_F47645AECE49579E (linkCanonical), INDEX IDX_F47645AE84A0A3ED (content_id), INDEX search_idx (linkCanonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', username VARCHAR(255) NOT NULL, usernameCanonical VARCHAR(255) NOT NULL, password VARCHAR(512) NOT NULL, email VARCHAR(512) NOT NULL, emailCanonical VARCHAR(255) NOT NULL, display_name VARCHAR(512) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_removed TINYINT(1) NOT NULL, create_date DATETIME NOT NULL, mod_date DATETIME NOT NULL, password_mod_date DATETIME NOT NULL, timezone VARCHAR(32) DEFAULT \'UTC\' NOT NULL, color VARCHAR(10) NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649F5A5DC32 (usernameCanonical), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649885281E (emailCanonical), INDEX search_idx (usernameCanonical, emailCanonical), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE waste (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', source_type VARCHAR(255) NOT NULL, source_name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, mod_date DATETIME NOT NULL, INDEX IDX_2E76A488A76ED395 (user_id), INDEX search_idx (source_type, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB620F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE page ADD CONSTRAINT FK_140AB6203DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE Page_categories ADD CONSTRAINT FK_B9D64968C4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE Page_categories ADD CONSTRAINT FK_B9D6496812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE Page_tags ADD CONSTRAINT FK_6A890A5FC4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE Page_tags ADD CONSTRAINT FK_6A890A5FBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
        $this->addSql('ALTER TABLE page_series ADD CONSTRAINT FK_55C6F733C4663E4 FOREIGN KEY (page_id) REFERENCES page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_series ADD CONSTRAINT FK_55C6F7335278319C FOREIGN KEY (series_id) REFERENCES series (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_reset_requests ADD CONSTRAINT FK_9075A748A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE revision ADD CONSTRAINT FK_6D6315CCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012D3DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE Series_pages ADD CONSTRAINT FK_4DCADA905278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE Series_pages ADD CONSTRAINT FK_4DCADA90C4663E4 FOREIGN KEY (page_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE url ADD CONSTRAINT FK_F47645AE84A0A3ED FOREIGN KEY (content_id) REFERENCES page (id)');
        $this->addSql('ALTER TABLE waste ADD CONSTRAINT FK_2E76A488A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB620F675F31B');
        $this->addSql('ALTER TABLE page DROP FOREIGN KEY FK_140AB6203DA5256D');
        $this->addSql('ALTER TABLE Page_categories DROP FOREIGN KEY FK_B9D64968C4663E4');
        $this->addSql('ALTER TABLE Page_categories DROP FOREIGN KEY FK_B9D6496812469DE2');
        $this->addSql('ALTER TABLE Page_tags DROP FOREIGN KEY FK_6A890A5FC4663E4');
        $this->addSql('ALTER TABLE Page_tags DROP FOREIGN KEY FK_6A890A5FBAD26311');
        $this->addSql('ALTER TABLE page_series DROP FOREIGN KEY FK_55C6F733C4663E4');
        $this->addSql('ALTER TABLE page_series DROP FOREIGN KEY FK_55C6F7335278319C');
        $this->addSql('ALTER TABLE password_reset_requests DROP FOREIGN KEY FK_9075A748A76ED395');
        $this->addSql('ALTER TABLE revision DROP FOREIGN KEY FK_6D6315CCA76ED395');
        $this->addSql('ALTER TABLE series DROP FOREIGN KEY FK_3A10012D3DA5256D');
        $this->addSql('ALTER TABLE Series_pages DROP FOREIGN KEY FK_4DCADA905278319C');
        $this->addSql('ALTER TABLE Series_pages DROP FOREIGN KEY FK_4DCADA90C4663E4');
        $this->addSql('ALTER TABLE url DROP FOREIGN KEY FK_F47645AE84A0A3ED');
        $this->addSql('ALTER TABLE waste DROP FOREIGN KEY FK_2E76A488A76ED395');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE login_activity');
        $this->addSql('DROP TABLE page');
        $this->addSql('DROP TABLE Page_categories');
        $this->addSql('DROP TABLE Page_tags');
        $this->addSql('DROP TABLE page_series');
        $this->addSql('DROP TABLE password_reset_requests');
        $this->addSql('DROP TABLE revision');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE Series_pages');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE url');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE waste');
    }
}
