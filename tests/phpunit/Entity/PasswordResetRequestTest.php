<?php

namespace App\Tests\phpunit\Entity;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PasswordResetRequestTest extends TestCase
{
    protected ?PasswordResetRequest $passwordResetRequest;

    protected ?User $user;

    protected \DateTimeImmutable $expiresAt;

    public function setUp(): void
    {
        $this->user = new User();
        $this->expiresAt = new \DateTimeImmutable();
        $this->passwordResetRequest = new PasswordResetRequest($this->user, 'abc123', $this->expiresAt);
        parent::setUp();
    }

    public function testGetUser()
    {
        $this->assertEquals($this->user, $this->passwordResetRequest->getUser());
    }

    public function testGetTokenHash()
    {
        $this->assertEquals('abc123', $this->passwordResetRequest->getTokenHash());
    }

    public function testGetCreatedAt()
    {
        $this->assertNotEmpty($this->passwordResetRequest->getCreatedAt());
    }

    public function testGetExpiresAt()
    {
        $this->assertEquals($this->expiresAt, $this->passwordResetRequest->getExpiresAt());
    }

    public function testMarkUsed()
    {
        $this->assertFalse($this->passwordResetRequest->isUsed());
        $this->passwordResetRequest->markUsed();
        $this->assertTrue($this->passwordResetRequest->isUsed());
    }
}