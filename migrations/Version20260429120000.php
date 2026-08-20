<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
        $analyticsPageView = $schema->createTable('analytics_page_view');
        $analyticsPageView->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $analyticsPageView->addColumn('path', 'string', [
            'length' => 255,
        ]);
        $analyticsPageView->addColumn('date', 'date');
        $analyticsPageView->addColumn('views', 'integer', [
            'default' => 0,
        ]);
        $analyticsPageView->setPrimaryKey(['id']);
        $analyticsPageView->addUniqueIndex(
            ['path', 'date'],
            'uniq_path_date',
        );
        $analyticsPageView->addIndex(
            ['date'],
            'idx_analytics_page_view_date',
        );

        $analyticsUniqueVisitor = $schema->createTable('analytics_unique_visitor');
        $analyticsUniqueVisitor->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $analyticsUniqueVisitor->addColumn('visitor_hash', 'string', [
            'length' => 64,
        ]);
        $analyticsUniqueVisitor->addColumn('date', 'date');
        $analyticsUniqueVisitor->setPrimaryKey(['id']);
        $analyticsUniqueVisitor->addUniqueIndex(
            ['visitor_hash', 'date'],
            'uniq_visitor_date',
        );
        $analyticsUniqueVisitor->addIndex(
            ['date'],
            'idx_analytics_unique_visitor_date',
        );

        $analyticsErrors = $schema->createTable('analytics_errors');
        $analyticsErrors->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $analyticsErrors->addColumn('path', 'string', [
            'length' => 255,
        ]);
        $analyticsErrors->addColumn('date', 'date');
        $analyticsErrors->addColumn('code', 'integer', [
            'default' => 0,
        ]);
        $analyticsErrors->addColumn('hits', 'integer', [
            'default' => 0,
        ]);
        $analyticsErrors->setPrimaryKey(['id']);
        $analyticsErrors->addUniqueIndex(
            ['path', 'date', 'code'],
            'uniq_path_date_code',
        );
        $analyticsErrors->addIndex(
            ['date'],
            'idx_analytics_errors_date',
        );
        $analyticsErrors->addIndex(
            ['code'],
            'idx_analytics_errors_code',
        );

        $analyticsReferrer = $schema->createTable('analytics_referrer');
        $analyticsReferrer->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $analyticsReferrer->addColumn('domain', 'string', [
            'length' => 255,
        ]);
        $analyticsReferrer->addColumn('path', 'string', [
            'length' => 255,
        ]);
        $analyticsReferrer->addColumn('date', 'date');
        $analyticsReferrer->addColumn('hits', 'integer', [
            'default' => 0,
        ]);
        $analyticsReferrer->setPrimaryKey(['id']);
        $analyticsReferrer->addUniqueIndex(
            ['domain', 'path', 'date'],
            'uniq_domain_path_date',
        );
        $analyticsReferrer->addIndex(
            ['domain'],
            'idx_analytics_referrer_domain',
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('analytics_page_view');
        $schema->dropTable('analytics_unique_visitor');
        $schema->dropTable('analytics_errors');
        $schema->dropTable('analytics_referrer');
    }
}
