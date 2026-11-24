<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Updater\Release;

use Inachis\Updater\Release\ManifestFactory;
use PHPUnit\Framework\TestCase;

final class ManifestFactoryTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new ManifestFactory();

        self::assertInstanceOf(
            ManifestFactory::class,
            $instance,
        );
    }
}
