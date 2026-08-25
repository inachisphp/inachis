<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Validator;

use Inachis\Validator\DkimValidator;
use PHPUnit\Framework\TestCase;

final class DkimValidatorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new DkimValidator();

        self::assertInstanceOf(
            DkimValidator::class,
            $instance,
        );
    }
}
