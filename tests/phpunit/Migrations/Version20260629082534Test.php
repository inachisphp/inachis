<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace DoctrineMigrations;

use DoctrineMigrations\Version20260629082534;
use PHPUnit\Framework\TestCase;

final class Version20260629082534Test extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new Version20260629082534();

        self::assertInstanceOf(
            Version20260629082534::class,
            $instance
        );
    }
}