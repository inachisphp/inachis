<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Validator;

use Inachis\Validator\DmarcValidator;
use PHPUnit\Framework\TestCase;

final class DmarcValidatorTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new DmarcValidator();

        self::assertInstanceOf(
            DmarcValidator::class,
            $instance
        );
    }
}