<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Post;

use Inachis\Service\Ai\AiTextManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Post SEO metadata API.
 */
class PostSeoMetadataController extends AbstractController
{
    #[Route('/incp/api/post/generate-seo-metadata', name: 'incp_api_post_ai_seo_metadata', methods: ['POST'])]
    public function generateSeoMetadata(
        Request $request,
        AiTextManager $aiTextManager,
    ): JsonResponse {
        if (!$aiTextManager->isConfigured()) {
            return new JsonResponse(['error' => 'AI Text feature is not configured.'], 400);
        }

        $payload = json_decode($request->getContent(), true) ?: $request->request->all();
        $content = $payload['content'] ?? '';
        $title = $payload['title'] ?? null;

        if (empty(trim(strip_tags($content)))) {
            return new JsonResponse(['error' => 'Please enter some article content before generating SEO metadata.'], 422);
        }

        try {
            $seoData = $aiTextManager->generateSeoMetadata($content, $title);

            return new JsonResponse([
                'success' => true,
                'data' => $seoData,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'SEO generation failed: '.$e->getMessage()], 500);
        }
    }
}
