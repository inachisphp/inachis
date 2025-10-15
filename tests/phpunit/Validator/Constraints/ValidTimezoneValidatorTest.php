<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Util;

use App\Validator\Constraints\ValidTimezone;
use App\Validator\Constraints\ValidTimezoneValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use stdClass;

class ValidTimezoneValidatorTest extends ConstraintValidatorTestCase
{
    protected ValidTimezoneValidator $validTimezoneValidator;

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidTimezoneValidator();
    }

    public function testValidateEmpty(): void
    {
        $this->assertEmpty($this->validator->validate('', new ValidTimezone()));
    }

    public function testValidateIncorrectContraint(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->assertEmpty($this->validator->validate('', new NotBlank()));
    }

    public function testValidateNotString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new stdClass(), new ValidTimezone());
    }

    public function testTimezoneNotInArray(): void
    {
        $this->validator->validate('Europe/Antarctica', new ValidTimezone());
        $this->buildViolation('"{{ string }}" is not a recognised timezone')
            ->setParameter('{{ string }}', 'Europe/Antarctica')
            ->assertRaised();
    }

    public function testValidate(): void
    {
        $this->validator->validate('Europe/London', new ValidTimezone());
        $this->assertNoViolation();
    }
}
