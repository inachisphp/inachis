<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Inachis\Entity\User\User;
use Inachis\Enum\Security\LoginResultType;
use Inachis\Security\Authentication\LoginSuccessRecorder;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class TwoFactorAuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginSuccessRecorder $loginSuccessRecorder,
        private readonly TrustedDeviceManager $trustedDeviceManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    /**
     * If the user has TOTP enabled, validate a trusted device cookie first. If 
     * the device is not trusted, redirect to TOTP verification.
     *
     * @param LoginSuccessEvent $event
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User || !$user->isTotpEnabled()) {
            return;
        }
        $trustedDeviceCookie = $this->trustedDeviceManager->validate(
            $user,
            $event->getRequest()
        );

        if ($trustedDeviceCookie !== null) {
            $this->loginSuccessRecorder->record(
                $user,
                $event->getRequest(),
                LoginResultType::TYPE_SUCCESS_TRUSTED
            );

            $response = new RedirectResponse(
                $this->urlGenerator->generate('incc_dashboard')
            );
            $response->headers->setCookie($trustedDeviceCookie);
            $event->setResponse($response);

            return;
        }

        $session = $event->getRequest()->getSession();
        $session->set('security.totp_pending', true);
        $session->set(
            'security.pending_2fa_target',
            $event->getRequest()->getUri()
        );

        $event->setResponse(
            new RedirectResponse(
                $this->urlGenerator->generate(
                    'inadmin_totp_login'
                )
            )
        );
    }
}
