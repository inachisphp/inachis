<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Form\Provider;

use Inachis\Form\Provider\LanguageCodes;
use PHPUnit\Framework\TestCase;

final class LanguageCodesTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new LanguageCodes();

        self::assertInstanceOf(
            LanguageCodes::class,
            $instance,
        );
    }
}
