<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Content\Page;

use Inachis\Service\Content\Page\UrlManager;
use PHPUnit\Framework\TestCase;

final class UrlManagerTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new UrlManager();

        self::assertInstanceOf(
            UrlManager::class,
            $instance
        );
    }
}