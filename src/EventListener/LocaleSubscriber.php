<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener for setting the locale
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private string $defaultLocale;

    /**
     * LocaleSubscriber constructor.
     * 
     * @param string $defaultLocale The default locale, defaults to 'en'
     */
    public function __construct(string $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * Handles kernel requests
     * 
     * @param RequestEvent $event The request event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }
        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $locale = $request->getSession()->get('_locale', $this->defaultLocale);
            if (!is_string($locale)) {
                $locale = $this->defaultLocale;
            }
            $request->setLocale($locale);
        }
    }

    /**
     * Returns the events this listener is subscribed to
     * 
     * @return array<array<string|int>> The events this listener is subscribed to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
