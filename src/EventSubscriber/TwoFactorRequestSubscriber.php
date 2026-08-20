<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
            'incp_account_login',
            'incp_totp_login',
            'incp_totp_login_verify',
            'incp_recovery_code_login',
            'incp_recovery_code_verify',
            'incp_logout',
        ];
        if (in_array($route, $allowedRoutes, true)) {
            return;
        }
        $event->setResponse(
            new RedirectResponse('/incp/login/totp'),
        );
    }
}
