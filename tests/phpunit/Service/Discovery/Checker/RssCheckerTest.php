<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Discovery\Checker;

use Inachis\Service\Discovery\Checker\RssChecker;
use PHPUnit\Framework\TestCase;

final class RssCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new RssChecker();

        self::assertInstanceOf(
            RssChecker::class,
            $instance,
        );
    }
}
