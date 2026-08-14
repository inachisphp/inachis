<?php

namespace Inachis\Controller\Page\Post;

use Inachis\Entity\Content\Page;
use Inachis\Service\Ai\AiAudioManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class PostAudioController
{
    private AiAudioManager $audioManager;

    public function __construct(AiAudioManager $audioManager)
    {
        $this->audioManager = $audioManager;
    }

    /**
     * Stream route using Symfony 8 Route attributes
     */
    #[Route('/incp/api/post/{id}/audio', name: 'api_post_audio_stream', methods: ['GET'])]
    public function streamAudio(Page $page, Request $request): Response
    {
        // 1. Authorization check
        // if (!$this->isGranted('CAN_READ', $page)) { ... }

        // 2. Fetch audio file using Ramsey UuidInterface from Page entity
        $filePath = $this->audioManager->getAudioFilePath($page->getId());
        
        if (!$filePath || !file_exists($filePath)) {
            return new Response('Audio file not found.', 404);
        }

        // 3. Stream binary MP3 response
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'audio/mpeg');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            sprintf('post_%s.mp3', (string) $page->getId())
        );

        $response->setAutoEtag();
        $response->setLastModified(new \DateTimeImmutable('@' . filemtime($filePath)));
        $response->setPublic();

        if ($response->isNotModified($request)) {
            return $response;
        }

        return $response;
    }
}
