<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\User;

use Inachis\Entity\User\LoginActivity;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\LoginResultType;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class LoginActivityTest extends TestCase
{
    private LoginActivity $loginActivity;

    protected function setUp(): void
    {
        $user = new User();
        $user->setUsername('username');

        $this->loginActivity = new LoginActivity(
            $user,
            LoginResultType::TYPE_SUCCESS,
        );

        parent::setUp();
    }

    public function testSetAndGetId(): void
    {
        $uuid = Uuid::uuid1();

        $this->loginActivity->setId($uuid);

        $this->assertSame($uuid, $this->loginActivity->getId());
    }

    public function testSetAndGetUsername(): void
    {
        $this->assertSame('username', $this->loginActivity->getUsername());

        $this->loginActivity->setUsername('another');

        $this->assertSame('another', $this->loginActivity->getUsername());
    }

    public function testSetAndGetIpAddress(): void
    {
        $this->assertNull($this->loginActivity->getIpAddress());

        $this->loginActivity->setIpAddress('127.0.0.1');

        $this->assertSame('127.0.0.1', $this->loginActivity->getIpAddress());
    }

    public function testSetAndGetUserAgent(): void
    {
        $this->assertNull($this->loginActivity->getUserAgent());

        $this->loginActivity->setUserAgent('PHPUnit');

        $this->assertSame('PHPUnit', $this->loginActivity->getUserAgent());
    }

    public function testSetAndGetLoggedAt(): void
    {
        $date = new \DateTimeImmutable();

        $this->loginActivity->setLoggedAt($date);

        $this->assertSame($date, $this->loginActivity->getLoggedAt());
    }

    public function testSetAndGetType(): void
    {
        $this->assertSame(
            LoginResultType::TYPE_SUCCESS,
            $this->loginActivity->getType(),
        );

        $this->loginActivity->setType(LoginResultType::TYPE_FAILURE);

        $this->assertSame(
            LoginResultType::TYPE_FAILURE,
            $this->loginActivity->getType(),
        );
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $user->setUsername('new-user');

        $this->loginActivity->setUser($user);

        $this->assertSame($user, $this->loginActivity->getUser());
    }

    public function testSetAndGetSessionHash(): void
    {
        $this->assertNull($this->loginActivity->getSessionHash());

        $this->loginActivity->setSessionHash('hash');

        $this->assertSame('hash', $this->loginActivity->getSessionHash());
    }

    public function testConstructorHashesSessionId(): void
    {
        $user = new User();
        $user->setUsername('username');

        $activity = new LoginActivity(
            $user,
            LoginResultType::TYPE_SUCCESS,
            sessionId: 'session-id',
        );

        $this->assertSame(
            hash('sha256', 'session-id'),
            $activity->getSessionHash(),
        );
    }

    public function testSetAndGetExtraData(): void
    {
        $this->assertNull($this->loginActivity->getExtraData());

        $data = [
            'key' => 'value',
        ];

        $this->loginActivity->setExtraData($data);

        $this->assertSame($data, $this->loginActivity->getExtraData());
    }

    public function testConstructorSetsLoggedAt(): void
    {
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $this->loginActivity->getLoggedAt(),
        );
    }

    public function testConstructorUsesExplicitUsername(): void
    {
        $user = new User();
        $user->setUsername('original');

        $activity = new LoginActivity(
            $user,
            LoginResultType::TYPE_SUCCESS,
            username: 'override',
        );

        $this->assertSame('override', $activity->getUsername());
    }
}