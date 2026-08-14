<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Post;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\Page;
use Inachis\Service\Ai\AiAudioManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PostAudioGeneratorController extends AbstractInachisController {
    #[Route('/incp/api/post/{id}/generate-audio', name: 'inadin_api_post_generate_audio', methods: ['POST'])]
    public function generateAudio(
        Page $page,
        Request $request,
        AiAudioManager $audioManager,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true) ?? [];
        $title = $payload['title'] ?? $page->getTitle();
        $content = $payload['content'] ?? $page->getContent() ?? '';

        if (empty(trim($content))) {
            return new JsonResponse([
                'success' => false, 
                'error' => 'Post content cannot be empty.'
            ], 400);
        }

        try {
            $result = $audioManager->getOrGeneratePostAudio(
                $page->getId(),
                $title,
                $content
            );

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'cached'   => $result['cached'],
                    'audioUrl' => $this->generateUrl('api_post_audio_stream', ['id' => (string) $page->getId()]),
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
