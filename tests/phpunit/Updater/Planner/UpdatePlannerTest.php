<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Updater\Planner;

use Inachis\Updater\Planner\UpdatePlanner;
use PHPUnit\Framework\TestCase;

final class UpdatePlannerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new UpdatePlanner();

        self::assertInstanceOf(
            UpdatePlanner::class,
            $instance,
        );
    }
}
