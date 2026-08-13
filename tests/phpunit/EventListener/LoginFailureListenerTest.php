<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\LoginActivity;
use Inachis\EventListener\LoginFailureListener;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

final class LoginFailureListenerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private RequestStack&MockObject $requestStack;
    private LoginFailureListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->listener = new LoginFailureListener(
            $this->entityManager,
            $this->requestStack,
        );
    }

    #[Test]
    public function itLogsFailedLoginAttemptWithSubmittedUsername(): void
    {
        $request = Request::create(
            '/login',
            'POST',
            ['login' => ['loginUsername' => 'john_doe']],
            [],
            [],
            [
                'REMOTE_ADDR' => '192.168.1.50',
                'HTTP_USER_AGENT' => 'Mozilla/5.0 TestAgent',
            ],
        );

        $exception = $this->createMock(AuthenticationException::class);
        $exception->expects(self::once())
            ->method('getMessageKey')
            ->willReturn('Invalid credentials.');

        $event = $this->createMock(LoginFailureEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::once())
            ->method('getException')
            ->willReturn($exception);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(LoginActivity::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        ($this->listener)($event);
    }

    #[Test]
    public function itLogsFailedLoginAttemptWhenUsernameKeyIsMissing(): void
    {
        $request = Request::create(
            '/login',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '10.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ],
        );

        $exception = $this->createMock(AuthenticationException::class);
        $exception->expects(self::once())
            ->method('getMessageKey')
            ->willReturn('Bad credentials.');

        $event = $this->createMock(LoginFailureEvent::class);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects(self::once())
            ->method('getException')
            ->willReturn($exception);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(LoginActivity::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        ($this->listener)($event);
    }
}
