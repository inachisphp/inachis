<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Entity;

use Inachis\Entity\PasswordResetRequest;
use Inachis\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PasswordResetRequestTest extends TestCase
{
    protected ?PasswordResetRequest $passwordResetRequest;

    protected ?User $user;

    protected DateTimeImmutable $expiresAt;

    public function setUp(): void
    {
        $this->user = new User();
        $this->expiresAt = new DateTimeImmutable();
        $this->passwordResetRequest = new PasswordResetRequest($this->user, 'abc123', $this->expiresAt);
        parent::setUp();
    }

    public function testGetId(): void
    {
        $this->assertEmpty($this->passwordResetRequest->getId());
    }

    public function testGetUser(): void
    {
        $this->assertEquals($this->user, $this->passwordResetRequest->getUser());
    }

    public function testGetTokenHash(): void
    {
        $this->assertEquals('abc123', $this->passwordResetRequest->getTokenHash());
    }

    public function testGetCreatedAt(): void
    {
        $this->assertNotEmpty($this->passwordResetRequest->getCreatedAt());
    }

    public function testGetExpiresAt(): void
    {
        $this->assertEquals($this->expiresAt, $this->passwordResetRequest->getExpiresAt());
    }

    public function testMarkUsed(): void
    {
        $this->assertFalse($this->passwordResetRequest->isUsed());
        $this->passwordResetRequest->markUsed();
        $this->assertTrue($this->passwordResetRequest->isUsed());
    }
}
