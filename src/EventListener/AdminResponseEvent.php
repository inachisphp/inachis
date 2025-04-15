<?php

namespace App\EventListener;

use App\Security\ContentSecurityPolicy;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AdminResponseEvent implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $csp;

    /**
     * AdminResponseEvent constructor.
     *
     * @param LoggerInterface    $logger
     */
    public function __construct(LoggerInterface $logger, $csp)
    {
        $this->logger = $logger;
        $this->csp = $csp;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        // Redirect to /setup if no admins
        // Redirect if not signed in
        // Redirect if password has expired
        //$request = $event->getRequest();
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $this->sendSecurityHeaders($event);
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller) && method_exists($controller[0], 'setDefaults')) {
            $controller[0]->setDefaults();
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST    => 'onKernelRequest',
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE   => 'onKernelResponse',
        ];
    }

    /**
     * @param ResponseEvent $event
     */
    public function sendSecurityHeaders(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $event->getResponse()->headers->set('X-XSS-Protection', '1; mode=block');
        $event->getResponse()->headers->set('X-Content-Type-Options', 'nosniff');
        // default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' ajax.googleapis.com,cdn.jsdelivr.net,cdnjs.cloudflare.com,unpkg.com,www.google-analytics.com; style-src 'self' data: cdn.jsdelivr.net,cdnjs.cloudflare.com,fonts.googleapis.com,maxcdn.bootstrapcdn.com,unpkg.com; img-src 'self' data: maps.googleapis.com,staticflickr.com; font-src 'self' fonts.gstatic.com,maxcdn.bootstrapcdn.com; connect-src 'self' cdn.jsdelivr.net,maps.googleapis.com; form-action 'self'; upgrade-insecure-requests; block-all-mixed-content
//        $cspHeader = ContentSecurityPolicy::getInstance()->getCSPEnforceHeader(
//            $this->csp
//        );
//        if (!empty($cspHeader)) {
//            $event->getResponse()->headers->set('Content-Security-Policy', $cspHeader);
//        }
//        $cspReportHeader = ContentSecurityPolicy::getInstance()->getCSPReportHeader(
//            $this->csp
//        );
//        if (!empty($cspReportHeader)) {
//            $event->getResponse()->headers->set('Content-Security-Policy-Report-Only', $cspReportHeader);
//        }
    }
}
