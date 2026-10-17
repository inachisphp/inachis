<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Message;

use Inachis\Message\CleanupLoginActivityMessage;
use PHPUnit\Framework\TestCase;

final class CleanupLoginActivityMessageTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CleanupLoginActivityMessage();

        self::assertInstanceOf(
            CleanupLoginActivityMessage::class,
            $instance,
        );
    }
}
