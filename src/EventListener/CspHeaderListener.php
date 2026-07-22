<?php

namespace Inachis\EventListener;

use Inachis\Service\System\Csp\CspHeaderManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onKernelResponse')]
class CspHeaderListener
{
    public function __construct(
        private readonly CspHeaderManager $cspHeaderManager
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $path = $request->getPathInfo();

        // 1. Admin Policy: Hard-coded & strict for safety
        if (str_starts_with($path, '/incp')) {
            // $adminCsp = "default-src 'self'; script-src 'self'; style-src 'self' fonts.googleapis.com cdn.jsdelivr.net; object-src 'none'; report-uri https://localhost/api/csp/report";
            // $response->headers->set('Content-Security-Policy', $adminCsp);
            return;
        }

        // 2. Frontend Policy: Pulled from fast Cache
        $frontendCsp = $this->cspHeaderManager->getFrontendHeaderConfig();
        if ($frontendCsp !== null) {
            $response->headers->set($frontendCsp['name'], $frontendCsp['value']);
        }
    }
}
