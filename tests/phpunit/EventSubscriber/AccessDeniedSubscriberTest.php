<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventSubscriber;

use Inachis\EventSubscriber\AccessDeniedSubscriber;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageView;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

final class AccessDeniedSubscriberTest extends TestCase
{
    private Environment&MockObject $twig;
    private PageViewFactory&MockObject $pageViewFactory;
    private HttpKernelInterface&MockObject $kernel;
    private AccessDeniedSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twig = $this->createMock(Environment::class);
        $this->pageViewFactory = $this->createMock(PageViewFactory::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);

        $this->subscriber = new AccessDeniedSubscriber(
            $this->twig,
            $this->pageViewFactory,
        );
    }

    #[Test]
    public function itReturnsSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::EXCEPTION => 'onException'],
            AccessDeniedSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function itDoesNothingWhenExceptionIsNotAccessDeniedHttpException(): void
    {
        $request = Request::create('/about');
        $exception = new NotFoundHttpException('Page not found');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->pageViewFactory->expects(self::never())->method('create');
        $this->pageViewFactory->expects(self::never())->method('createAdmin');
        $this->twig->expects(self::never())->method('render');

        $this->subscriber->onException($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function itDoesNothingWhenRequestPathIsApiRoute(): void
    {
        $request = Request::create('/api/v1/posts');
        $exception = new AccessDeniedHttpException('Access denied for API');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->pageViewFactory->expects(self::never())->method('create');
        $this->pageViewFactory->expects(self::never())->method('createAdmin');
        $this->twig->expects(self::never())->method('render');

        $this->subscriber->onException($event);

        self::assertNull($event->getResponse());
    }

    #[Test]
    public function itRendersAdminErrorPageWhenRequestPathIsAdminRoute(): void
    {
        $request = Request::create('/incp/settings');
        $exception = new AccessDeniedHttpException('Admin access restricted');

        /** @var PageView $viewModel */
        $viewModel = (new \ReflectionClass(PageView::class))->newInstanceWithoutConstructor();

        $this->pageViewFactory
            ->expects(self::once())
            ->method('createAdmin')
            ->willReturn($viewModel);

        $this->pageViewFactory
            ->expects(self::never())
            ->method('create');

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'inadmin/errors/access_denied.html.twig',
                [
                    'viewModel' => $viewModel,
                    'message' => 'Admin access restricted',
                ],
            )
            ->willReturn('<html>Access Denied</html>');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('<html>Access Denied</html>', $response->getContent());
    }

    #[Test]
    public function itRendersFrontendErrorPageWhenRequestPathIsFrontendRoute(): void
    {
        $request = Request::create('/protected-page');
        $exception = new AccessDeniedHttpException('Members only content');

        /** @var PageView $viewModel */
        $viewModel = (new \ReflectionClass(PageView::class))->newInstanceWithoutConstructor();

        $this->pageViewFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($viewModel);

        $this->pageViewFactory
            ->expects(self::never())
            ->method('createAdmin');

        $this->twig
            ->expects(self::once())
            ->method('render')
            ->with(
                'inadmin/errors/access_denied.html.twig',
                [
                    'viewModel' => $viewModel,
                    'message' => 'Members only content',
                ],
            )
            ->willReturn('<html>Access Denied</html>');

        $event = new ExceptionEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $this->subscriber->onException($event);

        $response = $event->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('<html>Access Denied</html>', $response->getContent());
    }
}
