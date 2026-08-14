<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Post;

use Inachis\Exception\Ai\AiException;
use Inachis\Service\Ai\AiExceptionResponseFactory;
use Inachis\Service\Ai\AiTextManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Post SEO metadata API.
 */
class PostSeoMetadataController extends AbstractController
{
    #[Route('/incp/api/post/generate-seo-metadata', name: 'incp_api_post_ai_seo_metadata', methods: ['POST'])]
    public function generateSeoMetadata(
        Request $request,
        AiExceptionResponseFactory $aiExceptionResponseFactory,
        AiTextManager $aiTextManager,
    ): JsonResponse {
        if (!$aiTextManager->isConfigured()) {
            return new JsonResponse([
                'error' => 'AI Text feature is not configured.',
                'code' => 'ai_configuration',
            ], Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent(), true) ?: $request->request->all();
        $content = $payload['content'] ?? '';
        $title = $payload['title'] ?? null;

        if (empty(trim(strip_tags($content)))) {
            return new JsonResponse([
                'error' => 'Please enter some article content before generating SEO metadata.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $seoData = $aiTextManager->generateSeoMetadata($content, $title);

            return new JsonResponse([
                'success' => true,
                'data' => $seoData,
            ]);
        } catch (AiException $e) {
            return $aiExceptionResponseFactory->create(
                $e,
                'seo_metadata',
            );
        }
    }
}
