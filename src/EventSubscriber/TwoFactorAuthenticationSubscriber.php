<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Inachis\Entity\User\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class TwoFactorAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    /**
     * If the user has TOTP enabled, then redirect to TOTP login next.
     * This will use the requests session rather than SessionInterface
     * to ensure the correct session for this firewall is in use.
     *
     * @param LoginSuccessEvent $event
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        if (
            $user->getTotp() === null ||
            $user->getTotp()->getEnabledAt() === null
        ) {
            return;
        }
        $session = $event->getRequest()->getSession();

        $session->set('security.totp_pending', true);

        $event->setResponse(
            new RedirectResponse('/incc/login/totp')
        );
    }
}
