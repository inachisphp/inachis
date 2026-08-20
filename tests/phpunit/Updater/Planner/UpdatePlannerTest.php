<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
