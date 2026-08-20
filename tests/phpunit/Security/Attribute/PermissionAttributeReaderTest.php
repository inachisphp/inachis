<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Security\Attribute;

use Inachis\Security\Attribute\PermissionAttributeReader;
use PHPUnit\Framework\TestCase;

final class PermissionAttributeReaderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new PermissionAttributeReader();

        self::assertInstanceOf(
            PermissionAttributeReader::class,
            $instance,
        );
    }
}
