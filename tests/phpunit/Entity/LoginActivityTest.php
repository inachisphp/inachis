<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\LoginActivity;
use DateTime;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class LoginActivityTest extends TestCase
{
    protected ?LoginActivity $loginActivity;

    public function setUp(): void
    {
        $this->loginActivity = new LoginActivity();
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


    public function testSetAndGetRemoteIp(): void
    {
        $this->assertEmpty($this->loginActivity->getRemoteIp());
        $this->loginActivity->setRemoteIp('ip');
        $this->assertEquals('ip', $this->loginActivity->getRemoteIp());
    }


    public function testSetAndGetUserAgent(): void
    {
        $this->assertEmpty($this->loginActivity->getUserAgent());
        $this->loginActivity->setUserAgent('user-agent');
        $this->assertEquals('user-agent', $this->loginActivity->getUserAgent());
    }

    public function testSetAndGetTimestamp(): void
    {
        $date = new DateTime();
        $this->loginActivity->setTimestamp($date);
        $this->assertEquals($date, $this->loginActivity->getTimestamp());
    }
    public function testSetAndGetAttemptCount(): void
    {
        $this->assertEquals(1, $this->loginActivity->getAttemptCount());
        $this->loginActivity->setAttemptCount(10);
        $this->assertEquals(10, $this->loginActivity->getAttemptCount());
    }
}