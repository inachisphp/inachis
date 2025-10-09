<?php

namespace App\Tests\phpunit\Util;

use App\Validator\UserValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class UserValidatorTest extends TestCase
{
    protected $userValidator;

    public function setUp() : void
    {
        $this->userValidator = new UserValidator();

        parent::setUp();
    }

    public function testValidateUsernameEmpty() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validateUsername('');
    }

    public function testValidateUsernameInvalid() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validateUsername('something-wrong');
    }

    public function testValidateUsernameValid() : void
    {
        $this->assertEquals('test_user', $this->userValidator->validateUsername('test_user'));
    }

    public function testValidatePasswordEmpty() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validatePassword('');
    }

    public function testValidatePasswordInvalid() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validatePassword('short');
    }

    public function testValidatePasswordValid() : void
    {
        $this->assertEquals('jUstr1ght%', $this->userValidator->validatePassword('jUstr1ght%'));
    }

    public function testValidateEmailEmpty() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validateEmail('');
    }

    public function testValidateEmailInvaid() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validateEmail('notvalid');
    }

    public function testValidateEmailValid() : void
    {
        $this->assertEquals('test\'s@example.com', $this->userValidator->validateEmail('test\'s@example.com'));
    }

    public function testValidateDisplayNameEmpty() : void
    {
        $this->expectException('InvalidArgumentException');
        $this->userValidator->validateDisplayName('');
    }

    public function testValidateDisplayNameValid() : void
    {
        $this->assertEquals('A Name', $this->userValidator->validateDisplayName('A Name'));
    }
}
