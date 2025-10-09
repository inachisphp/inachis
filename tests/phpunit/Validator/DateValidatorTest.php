<?php

namespace App\Tests\phpunit\Util;

use App\Utils\UrlNormaliser;
use App\Validator\DateValidator;
use PHPUnit\Framework\TestCase;
use App\Exception\InvalidTimezoneException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class DateValidatorTest extends TestCase
{
    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezone() : void
    {
        $this->assertEquals('UTC', DateValidator::validateTimezone('UTC'));
        $this->assertEquals('Europe/London', DateValidator::validateTimezone('Europe/London'));
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezoneInvalidArgumentException() : void
    {
        $this->expectException(InvalidArgumentException::class);
        DateValidator::validateTimezone('');
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testValidateTimezoneInvalidTimezoneException() : void
    {
        $this->expectException(InvalidTimezoneException::class);
        DateValidator::validateTimezone('Europe\London');
    }
}
