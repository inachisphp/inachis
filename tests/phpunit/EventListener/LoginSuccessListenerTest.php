<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\LoginResultType;
use Inachis\EventListener\LoginSuccessListener;
use Inachis\Security\Authentication\LoginSuccessRecorder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginSuccessListenerTest extends TestCase
{
    private LoginSuccessRecorder&MockObject $recorder;
    private LoginSuccessListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recorder = $this->createMock(LoginSuccessRecorder::class);
        $this->listener = new LoginSuccessListener($this->recorder);
    }

    #[Test]
    public function itDoesNothingWhenUserIsNotInachisUser(): void
    {
        $otherUser = $this->createMock(UserInterface::class);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($otherUser);

        $this->recorder
            ->expects(self::never())
            ->method('record');

        ($this->listener)($event);
    }

    #[Test]
    public function itDoesNothingWhenTwoFactorAuthenticationIsPending(): void
    {
        $user = $this->createMock(User::class);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('has')
            ->with('security.2fa_pending')
            ->willReturn(true);

        $request = Request::create('/login');
        $request->setSession($session);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $event->expects(self::once())
            ->method('getRequest')
            ->willReturn($request);

        $this->recorder
            ->expects(self::never())
            ->method('record');

        ($this->listener)($event);
    }

    #[Test]
    public function itRecordsLoginSuccessWhenUserIsValidAndTwoFactorNotPending(): void
    {
        $user = $this->createMock(User::class);

        $session = $this->createMock(SessionInterface::class);
        $session->expects(self::once())
            ->method('has')
            ->with('security.2fa_pending')
            ->willReturn(false);

        $request = Request::create('/login');
        $request->setSession($session);

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $event->expects(self::exactly(2))
            ->method('getRequest')
            ->willReturn($request);

        $this->recorder
            ->expects(self::once())
            ->method('record')
            ->with($user, $request, LoginResultType::TYPE_SUCCESS);

        ($this->listener)($event);
    }
}
