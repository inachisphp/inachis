<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Service\Page\ContentAggregator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Default controller for the application
 */
class DefaultController extends AbstractInachisController
{
    /**
     * Homepage
     *
     * @param ContentAggregator $contentProvider
     * @return Response
     */
    #[Route("/", methods: [ "GET" ])]
    public function homepage(ContentAggregator $contentProvider): Response
    {
        $this->data['content'] = $contentProvider->getHomepageContent();
        return $this->render('web/pages/homepage.html.twig', $this->data);
    }

    /**
     * Health check
     *
     * @return JsonResponse
     */
    #[Route("/health", methods: [ "GET" ])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'time' => time(),
    ]);
}
}
