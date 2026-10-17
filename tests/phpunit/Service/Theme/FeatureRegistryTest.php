<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Service\Theme;

use Inachis\Service\Theme\FeatureRegistry;
use PHPUnit\Framework\TestCase;

final class FeatureRegistryTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new FeatureRegistry();

        self::assertInstanceOf(
            FeatureRegistry::class,
            $instance,
        );
    }
}
