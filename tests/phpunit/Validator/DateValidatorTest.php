<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Util;

use Inachis\Exception\InvalidTimezoneException;
use Inachis\Validator\DateValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class DateValidatorTest extends TestCase
{
    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezone(): void
    {
        $this->assertEquals('UTC', DateValidator::validateTimezone('UTC'));
        $this->assertEquals('Europe/London', DateValidator::validateTimezone('Europe/London'));
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezoneInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DateValidator::validateTimezone('');
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezoneInvalidTimezoneException(): void
    {
        $this->expectException(InvalidTimezoneException::class);
        DateValidator::validateTimezone('Europe\London');
    }
}
