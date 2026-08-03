<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractController;
use Inachis\Service\Discovery\Generator\LlmsTxtGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for serving the llms.txt file
 */
class LlmsTxtWebController extends AbstractController
{
    /**
     * Serve the llms.txt content
     *
     * @param LlmsTxtGenerator $generator
     * @return Response
     */
    #[Route('/llms.txt', name: 'web_llms_txt')]
    public function index(
        LlmsTxtGenerator $generator
    ): Response {
        return new Response(
            $generator->generate(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]
        );
    }
}
