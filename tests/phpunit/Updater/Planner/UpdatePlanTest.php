<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater\Planner;

use Inachis\Updater\Planner\UpdatePlan;
use PHPUnit\Framework\TestCase;

class UpdatePlanTest extends TestCase
{
    public function testConstructorInitializesRequiredPropertiesWithDefaults(): void
    {
        $plan = new UpdatePlan(
            currentVersion: '1.0.0',
            targetVersion: '2.0.0',
            package: 'inachis-2.0.0.zip',
            archiveUrl: 'https://example.com/inachis-2.0.0.zip',
            replacePaths: ['src/', 'bin/'],
            preservePaths: ['config/', 'var/'],
            migrations: ['Version20260101000000.php'],
            requiresMigration: true,
        );

        $this->assertSame('1.0.0', $plan->currentVersion);
        $this->assertSame('2.0.0', $plan->targetVersion);
        $this->assertSame('inachis-2.0.0.zip', $plan->package);
        $this->assertSame('https://example.com/inachis-2.0.0.zip', $plan->archiveUrl);
        $this->assertSame(['src/', 'bin/'], $plan->replacePaths);
        $this->assertSame(['config/', 'var/'], $plan->preservePaths);
        $this->assertSame(['Version20260101000000.php'], $plan->migrations);
        $this->assertTrue($plan->requiresMigration);

        // Verify default 'type' value
        $this->assertSame('core', $plan->type);
    }

    public function testConstructorInitializesPropertiesWithCustomValuesAndNullArchiveUrl(): void
    {
        $plan = new UpdatePlan(
            currentVersion: '2.0.0',
            targetVersion: '2.1.0',
            package: 'plugin-test-1.0.0.zip',
            archiveUrl: null,
            replacePaths: [],
            preservePaths: [],
            migrations: [],
            requiresMigration: false,
            type: 'plugin',
        );

        $this->assertSame('2.0.0', $plan->currentVersion);
        $this->assertSame('2.1.0', $plan->targetVersion);
        $this->assertSame('plugin-test-1.0.0.zip', $plan->package);
        $this->assertNull($plan->archiveUrl);
        $this->assertSame([], $plan->replacePaths);
        $this->assertSame([], $plan->preservePaths);
        $this->assertSame([], $plan->migrations);
        $this->assertFalse($plan->requiresMigration);
        $this->assertSame('plugin', $plan->type);
    }
}
