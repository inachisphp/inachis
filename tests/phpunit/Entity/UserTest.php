<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Entity;

use App\Entity\Image;
use App\Entity\User;
use App\Exception\InvalidTimezoneException;
use DateTime;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UserTest extends TestCase
{
    protected ?User $user;

    public function setUp(): void
    {
        $this->user = new User();
        parent::setUp();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->user->setId($uuid);
        $this->assertEquals($uuid, $this->user->getId());
    }

    public function testSetAndGetUsername(): void
    {
        $this->user->setUsername('test');
        $this->assertEquals('test', $this->user->getUsername());
    }

    public function testSetAndGetPassword(): void
    {
        $this->user->setPassword('test');
        $this->assertEquals('test', $this->user->getPassword());
    }

    public function testSetAndGetPlainPassword(): void
    {
        $this->user->setPlainPassword('test');
        $this->assertEquals('test', $this->user->getPlainPassword());
    }

    public function testSetAndGetEmail(): void
    {
        $this->user->setEmail('test');
        $this->assertEquals('test', $this->user->getEmail());
    }

    public function testSetAndGetDisplayName(): void
    {
        $this->user->setDisplayName('test user');
        $this->assertEquals('test user', $this->user->getDisplayName());
    }

    public function testSetAndGetInitials(): void
    {
        $this->user->setDisplayName('test');
        $this->assertEquals('T', $this->user->getInitials());
        $this->user->setDisplayName('test user');
        $this->assertEquals('TU', $this->user->getInitials());
        $this->user->setDisplayName('test user-hyphenated');
        $this->assertEquals('TU', $this->user->getInitials());
        $this->user->setDisplayName('forename middle-name surname');
        $this->assertEquals('FMS', $this->user->getInitials());
    }

    public function testSetAndGetColor(): void
    {
        $this->user->setColor('#069');
        $this->assertEquals('#069', $this->user->getColor());
    }

    public function testSetAndGetAvatar(): void
    {
        $image = new Image();
        $this->user->setAvatar($image);
        $this->assertEquals($image, $this->user->getAvatar());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->user->isEnabled());
        $this->user->setActive(false);
        $this->assertFalse($this->user->isEnabled());
    }

    public function testHasBeenRemoved(): void
    {
        $this->assertFalse($this->user->hasBeenRemoved());
        $this->user->setRemoved(true);
        $this->assertTrue($this->user->hasBeenRemoved());
    }

    public function testSetAndGetCreateDate(): void
    {
        $currentDateTime = new DateTime('now');
        $this->user->setCreateDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getCreateDate());
    }

    public function testSetAndGetModDate(): void
    {
        $currentDateTime = new DateTime('now');
        $this->user->setModDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getModDate());
    }

    public function testSetAndGetPasswordModDate(): void
    {
        $currentDateTime = new DateTime('now');
        $this->user->setPasswordModDate($currentDateTime);
        $this->assertEquals($currentDateTime, $this->user->getPasswordModDate());
    }

    public function testHasCredentialsExpired(): void
    {
        $this->assertFalse($this->user->hasCredentialsExpired());
        $this->user->setPasswordModDate(new DateTime('-20 days'));
        $this->assertTrue($this->user->hasCredentialsExpired(10));
    }

    public function testValidateEmail(): void
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

    public function testGetRoles(): void
    {
        $this->user->setRoles([ 'ROLE_ADMIN', 'ROLE_USER' ]);
        $this->assertEquals([ 'ROLE_ADMIN', 'ROLE_USER' ], $this->user->getRoles());
    }

    public function testGetUserIdentifier(): void
    {
        $this->assertEquals($this->user->getUsername(), $this->user->getUserIdentifier());
    }

    public function testEraseCredentials(): void
    {
        $this->user->setPlainPassword('test');
        $this->assertEquals('test', $this->user->getPlainPassword());
        $this->user->eraseCredentials();
        $this->assertEquals('', $this->user->getPlainPassword());
    }

    public function testErase(): void
    {
        $this->assertNull($this->user->erase());
    }

    /**
     * @throws InvalidTimezoneException
     */
    public function testSetAndGetTimezone(): void
    {
        $this->user->setTimezone('Europe/London');
        $this->assertEquals('Europe/London', $this->user->getTimezone());
        $this->expectException(InvalidTimezoneException::class);
        $this->user->setTimezone('Alpha Centauri');
    }
}
