<?php

namespace App\Tests\phpunit\Util;

use App\Validator\Constraints\ValidIPAddress;
use App\Validator\Constraints\ValidIPAddressValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidIPAddressValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidIPAddressValidator();
    }

    public function testValidate()
    {
        $this->assertEmpty($this->validator->validate('', new ValidIPAddress()));
        $this->assertEmpty($this->validator->validate('127.0.0.1', new ValidIPAddress()));
        $this->assertEmpty($this->validator->validate(
            '2001:0db8:85a3:0000:0000:8a2e:0370:7334', // full-format
            new ValidIPAddress()
        ));
        $this->assertEmpty($this->validator->validate(
            '2001:db8:3333:4444:5555:6666:7777:8888', // omit leading zeroes
            new ValidIPAddress()
        ));
        $this->assertNoViolation();
    }

    public function testValidateIPv4(): void
    {
        $this->assertTrue($this->validator->validateIPv4('127.0.0.1'));
        $this->assertTrue($this->validator->validateIPv4('1.2.3.4'));
        $this->assertFalse($this->validator->validateIPv4('256.0.0.1'));
        $this->assertFalse($this->validator->validateIPv4('256.0.0'));
    }

    public function testValidateIPv6(): void
    {
        $this->assertTrue($this->validator->validateIPv6('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertTrue($this->validator->validateIPv6('2001:db8:3333:4444:5555:6666:7777:8888'));
        $this->assertFalse($this->validator->validateIPv6('127.0.1.1'));
        $this->assertFalse($this->validator->validateIPv6('ff02::1::2'));
    }

    public function testValidateIncorrentConstraintThrowsException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('127.0.0.1', new NotBlank());
    }

    public function testValidateNonStringThrowsException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate([], new ValidIPAddress());
    }

    public function testValidateNotValidIPViolation(): void
    {
        $this->validator->validate('127.0.1', new ValidIPAddress());
        $this->buildViolation('"{{ string }}" is not a valid IPv4 or IPv6 address')
            ->setParameter('{{ string }}', '127.0.1')
            ->assertRaised();
    }
}
