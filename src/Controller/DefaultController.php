<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller;

use Inachis\Service\Content\Page\ContentAggregator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Default controller for the application.
 */
class DefaultController extends AbstractWebController
{
    /**
     * Homepage.
     */
    #[Route('/', methods: ['GET'])]
    public function homepage(ContentAggregator $contentProvider): Response
    {
        return $this->render('web/pages/homepage.html.twig', [
            'viewModel' => $this->viewModel,
            'content' => $contentProvider->getHomepageContent(),
        ]);
    }

    /**
     * Health check.
     */
    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'time' => time(),
        ]);
    }
}
