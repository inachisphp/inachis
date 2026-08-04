<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater\Verify;

use Inachis\Updater\Verify\ReleaseVerifier;
use PHPUnit\Framework\TestCase;

final class ReleaseVerifierTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ReleaseVerifier();

        self::assertInstanceOf(
            ReleaseVerifier::class,
            $instance
        );
    }
}