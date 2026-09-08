<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\API\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Page;
use Inachis\Service\Ai\AiAudioManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PostAudioGeneratorController extends AbstractInachisController {
    #[Route('/incp/api/post/{id}/generate-audio', name: 'incp_api_post_generate_audio', methods: ['POST'])]
    public function generateAudio(
        Page $page,
        Request $request,
        AiAudioManager $audioManager,
    ): JsonResponse {
        $id = $page->getId();
        if (null === $id) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Post ID cannot be null.',
            ], 400);
        }

        /** @var mixed $decoded */
        $decoded = json_decode($request->getContent(), true);
        $payload = is_array($decoded) ? $decoded : [];

        $rawTitle = $payload['title'] ?? $page->getTitle();
        $rawContent = $payload['content'] ?? $page->getContent() ?? '';

        $title = is_string($rawTitle) ? $rawTitle : '';
        $content = is_string($rawContent) ? $rawContent : '';

        if (empty(trim($content))) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Post content cannot be empty.'
            ], 400);
        }

        try {
            $result = $audioManager->getOrGeneratePostAudio(
                $page,
            );

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'cached'   => $result['cached'],
                    'audioUrl' => $this->generateUrl('web_post_audio_stream', ['id' => (string) $id]),
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false, 
                'error'   => 'Failed to generate audio: ' . $e->getMessage()
            ], 500);
        }
    }
}
