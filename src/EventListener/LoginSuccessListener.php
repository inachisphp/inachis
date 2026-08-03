<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventListener;

use Inachis\Entity\User\{LoginActivity, User};
use Inachis\Enum\Security\LoginResultType;
use Inachis\Security\Authentication\LoginSuccessRecorder;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * LoginSuccessListener for logging successful login attempts.
 */
class LoginSuccessListener
{
    /**
     * @param LoginSuccessRecorder $recorder
     */
    public function __construct(
        protected readonly LoginSuccessRecorder $recorder,
    ) {}

    /**
     * Logs a successful login attempt.
     *
     * @param LoginSuccessEvent $event
     */
    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        // If TOTP is pending, the login is not yet complete.
        if ($event->getRequest()->getSession()->has('security.2fa_pending')) {
            return;
        }

        $this->recorder->record(
            $user,
            $event->getRequest(),
            LoginResultType::TYPE_SUCCESS,
        );
    }  
}
