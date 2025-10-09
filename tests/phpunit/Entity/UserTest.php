<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\Image;
use App\Entity\User;
use App\Exception\InvalidTimezoneException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UserTest extends TestCase
{
    protected ?User $user;

    public function setUp() : void
    {
        $this->user = new User();

        parent::setUp();
    }

    public function testSetAndGetUsername()
    {
        $this->user->setUsername('test');
        $this->assertEquals('test', $this->user->getUsername());
    }

    public function testSetAndGetPassword()
    {
        $this->user->setPassword('test');
        $this->assertEquals('test', $this->user->getPassword());
    }

    public function testSetAndGetPlainPassword()
    {
        $this->user->setPlainPassword('test');
        $this->assertEquals('test', $this->user->getPlainPassword());
    }

    public function testSetAndGetEmail()
    {
        $this->user->setEmail('test');
        $this->assertEquals('test', $this->user->getEmail());
    }

    public function testSetAndGetDisplayName()
    {
        $this->user->setDisplayName('test');
        $this->assertEquals('test', $this->user->getDisplayName());
    }

    public function testSetAndGetAvatar()
    {
        $image = new Image();
        $this->user->setAvatar($image);
        $this->assertEquals($image, $this->user->getAvatar());
    }

    public function testIsEnabled()
    {
        $this->assertTrue($this->user->isEnabled());
        $this->user->setActive(false);
        $this->assertFalse($this->user->isEnabled());
    }

    public function testHasBeenRemoved()
    {
        $this->assertFalse($this->user->hasBeenRemoved());
        $this->user->setRemoved(true);
        $this->assertTrue($this->user->hasBeenRemoved());
    }

    public function testSetAndGetCreateDate()
    {
        $currentDateTime = new \DateTime('now');
        $this->user->setCreateDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getCreateDate());
    }

    public function testSetAndGetModDate()
    {
        $currentDateTime = new \DateTime('now');
        $this->user->setModDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getModDate());
    }

    public function testSetAndGetPasswordModDate()
    {
        $currentDateTime = new \DateTime('now');
        $this->user->setPasswordModDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getPasswordModDate());
    }

    public function testHasCredentialsExpired()
    {
        $this->assertFalse($this->user->hasCredentialsExpired());
        $this->user->setPasswordModDate(new \DateTime('-20 days'));
        $this->assertTrue($this->user->hasCredentialsExpired(10));
    }

    public function testValidateEmail()
    {
        $this->user->setEmail('test@test.com');
        $this->assertTrue($this->user->validateEmail());
        $this->user->setEmail('test@test.co.uk');
        $this->assertTrue($this->user->validateEmail());
        $this->user->setEmail('test.o\'test@test.com');
        $this->assertTrue($this->user->validateEmail());
        $this->user->setEmail('test+something@test.com');
        $this->assertTrue($this->user->validateEmail());
        $this->user->setEmail('test_at_test.com');
        $this->assertFalse($this->user->validateEmail());
    }

    public function testGetRoles()
    {
        $this->user->setRoles([ 'ROLE_ADMIN', 'ROLE_USER' ]);
        $this->assertEquals([ 'ROLE_ADMIN', 'ROLE_USER' ], $this->user->getRoles());
    }

    public function testEraseCredentials()
    {
        $this->user->setPlainPassword('test');
        $this->assertEquals('test', $this->user->getPlainPassword());
        $this->user->eraseCredentials();
        $this->assertEquals('', $this->user->getPlainPassword());
    }

    public function testErase()
    {
        $this->assertNull($this->user->erase());
    }

    public function testSetAndGetTimezone()
    {
        $this->user->setTimezone('Europe/London');
        $this->assertEquals('Europe/London', $this->user->getTimezone());
        $this->expectException(InvalidTimezoneException::class);
        $this->user->setTimezone('Alpha Centauri');
    }
}
