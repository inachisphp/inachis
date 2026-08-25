<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Validator;

use Inachis\Validator\SpfValidator;
use PHPUnit\Framework\TestCase;

final class SpfValidatorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SpfValidator();

        self::assertInstanceOf(
            SpfValidator::class,
            $instance,
        );
    }
}
