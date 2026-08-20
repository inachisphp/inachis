<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventSubscriber;

use Inachis\Factory\PageViewFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class AccessDeniedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private readonly PageViewFactory $pageViewFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!$exception instanceof AccessDeniedHttpException) {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $viewModel = str_starts_with($request->getPathInfo(), '/incp')
            ? $this->pageViewFactory->createAdmin()
            : $this->pageViewFactory->create();

        $response = new Response(
            $this->twig->render('inadmin/errors/access_denied.html.twig', [
                'viewModel' => $viewModel,
                'message' => $exception->getMessage(),
            ]),
            Response::HTTP_FORBIDDEN,
        );

        $event->setResponse($response);
    }
}
