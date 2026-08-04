<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Validator;

use Inachis\Validator\MxValidator;
use PHPUnit\Framework\TestCase;

final class MxValidatorTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new MxValidator();

        self::assertInstanceOf(
            MxValidator::class,
            $instance
        );
    }
}