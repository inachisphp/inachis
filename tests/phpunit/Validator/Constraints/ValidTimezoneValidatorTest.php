<?php

namespace App\Tests\phpunit\Util;

use App\Validator\Constraints\ValidTimezone;
use App\Validator\Constraints\ValidTimezoneValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Tests\Fixtures\ConstraintA;

class ValidTimezoneValidatorTest extends TestCase
{
    protected $validTimezoneValidator;

    public function setUp() : void
    {
        $this->validTimezoneValidator = new ValidTimezoneValidator();

        parent::setUp();
    }

    public function testValidateEmpty() : void
    {
        $this->assertEmpty($this->validTimezoneValidator->validate('', new ValidTimezone()));
    }

//    public function testValidateInvalidConstraint() : void
//    {
//        $this->expectException('Symfony\Component\Validator\Exception\UnexpectedValueException');
//        $this->validTimezoneValidator->validate('', new ConstraintA());
//    }

    public function testValidateNotString() : void
    {
        $this->expectException('Symfony\Component\Validator\Exception\UnexpectedValueException');
        $this->validTimezoneValidator->validate(new \stdClass(), new ValidTimezone());
    }
}
