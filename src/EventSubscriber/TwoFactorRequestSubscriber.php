<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TwoFactorRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session->get('security.totp_pending')) {
            return;
        }

        $route = $request->attributes->get('_route');

        $allowedRoutes = [
            'incc_account_login',
            'inadmin_totp_login',
            'inadmin_totp_login_verify',
            'inadmin_recovery_code_login',
            'inadmin_recovery_code_verify',
            'incc_logout',
        ];
        if (in_array($route, $allowedRoutes, true)) {
            return;
        }
        $event->setResponse(
            new RedirectResponse('/incc/login/totp')
        );
    }
}