<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\LoginActivity;
use Inachis\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class LoginActivityTest extends TestCase
{
    protected ?LoginActivity $loginActivity;

    public function setUp(): void
    {
        $user = $this->createStub(User::class);
        $this->loginActivity = new LoginActivity($user, 'success');
        parent::setUp();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();
        $this->loginActivity->setId($uuid);
        $this->assertEquals($uuid, $this->loginActivity->getId());
    }

    public function testSetAndGetUsername(): void
    {
        $this->loginActivity->setUsername('username');
        $this->assertEquals('username', $this->loginActivity->getUsername());
    }


    public function testSetAndGetIpAddress(): void
    {
        $this->assertEmpty($this->loginActivity->getIpAddress());
        $this->loginActivity->setIpAddress('ip');
        $this->assertEquals('ip', $this->loginActivity->getIpAddress());
    }


    public function testSetAndGetUserAgent(): void
    {
        $this->assertEmpty($this->loginActivity->getUserAgent());
        $this->loginActivity->setUserAgent('user-agent');
        $this->assertEquals('user-agent', $this->loginActivity->getUserAgent());
    }

    public function testSetAndGetLoggedAt(): void
    {
        $date = new DateTimeImmutable();
        $this->loginActivity->setLoggedAt($date);
        $this->assertEquals($date, $this->loginActivity->getLoggedAt());
    }
}
