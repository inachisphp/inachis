<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\EventListener;

use Inachis\EventListener\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriberTest extends TestCase
{
    private function createEvent(Request $request): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testSetsLocaleFromRequestAttribute(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['hasPreviousSession'])
            ->getMock();
        $request->setSession($session);
        $request->attributes->set('_locale', 'fr');
        $request->expects($this->once())
            ->method('hasPreviousSession')->willReturn(true);
        $subscriber = new LocaleSubscriber('en');
        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertSame('fr', $session->get('_locale'));
    }

    public function testNoPreviousSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['hasPreviousSession'])
            ->getMock();
        $request->setSession($session);
        $request->attributes->set('_locale', 'fr');
        $request->expects($this->once())
            ->method('hasPreviousSession')->willReturn(false);
        $subscriber = new LocaleSubscriber('en');
        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertSame(null, $session->get('_locale'));
    }

    public function testSetsLocaleFromSessionIfNoAttribute(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('_locale', 'de');
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['hasPreviousSession'])
            ->getMock();
        $request->expects($this->once())
            ->method('hasPreviousSession')->willReturn(true);
        $request->setSession($session);
        $subscriber = new LocaleSubscriber();
        $event = $this->createEvent($request);
        $subscriber->onKernelRequest($event);

        $this->assertSame('de', $request->getLocale());
    }

    public function testUsesDefaultLocaleIfNoSessionLocale(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = $this
            ->getMockBuilder(Request::class)
            ->onlyMethods(['hasPreviousSession'])
            ->getMock();
        $request->setSession($session);
        $request->expects($this->once())
            ->method('hasPreviousSession')->willReturn(true);

        $subscriber = new LocaleSubscriber('it');
        $event = $this->createEvent($request);

        $subscriber->onKernelRequest($event);

        $this->assertSame('it', $request->getLocale());
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = LocaleSubscriber::getSubscribedEvents();
        $this->assertIsArray($subscribedEvents);
        $this->assertArrayHasKey(KernelEvents::REQUEST, $subscribedEvents);
    }
}
