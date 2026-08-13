<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventListener;

use Inachis\EventListener\CspHeaderListener;
use Inachis\Service\System\Csp\CspHeaderManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CspHeaderListenerTest extends TestCase
{
    private CspHeaderManager&MockObject $cspHeaderManager;
    private HttpKernelInterface&MockObject $kernel;
    private CspHeaderListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cspHeaderManager = $this->createMock(CspHeaderManager::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->listener = new CspHeaderListener($this->cspHeaderManager);
    }

    #[Test]
    public function itDoesNothingWhenNotMainRequest(): void
    {
        $request = Request::create('/about');
        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response,
        );

        $this->cspHeaderManager
            ->expects(self::never())
            ->method('getFrontendHeaderConfig');

        $this->listener->onKernelResponse($event);

        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }

    #[Test]
    public function itDoesNothingForAdminPaths(): void
    {
        $request = Request::create('/incp/dashboard');
        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->cspHeaderManager
            ->expects(self::never())
            ->method('getFrontendHeaderConfig');

        $this->listener->onKernelResponse($event);

        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }

    #[Test]
    public function itSetsFrontendCspHeaderWhenConfigIsPresent(): void
    {
        $request = Request::create('/blog/post-1');
        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->cspHeaderManager
            ->expects(self::once())
            ->method('getFrontendHeaderConfig')
            ->willReturn([
                'name' => 'Content-Security-Policy',
                'value' => "default-src 'self'",
            ]);

        $this->listener->onKernelResponse($event);

        self::assertTrue($response->headers->has('Content-Security-Policy'));
        self::assertSame(
            "default-src 'self'",
            $response->headers->get('Content-Security-Policy'),
        );
    }

    #[Test]
    public function itDoesNotSetHeaderWhenConfigIsNull(): void
    {
        $request = Request::create('/contact');
        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        $this->cspHeaderManager
            ->expects(self::once())
            ->method('getFrontendHeaderConfig')
            ->willReturn(null);

        $this->listener->onKernelResponse($event);

        self::assertFalse($response->headers->has('Content-Security-Policy'));
    }
}
