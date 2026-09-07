<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Controller\Page\Post;

use Inachis\Entity\Content\Page;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Media\AudioRepository;
use Inachis\Service\Resource\ResourceStorageProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class PostAudioController
{
    public function __construct(
        private readonly AudioRepository $audioRepository,
        private readonly ResourceStorageProvider $storageProvider,
    ) {}

    #[Route('/post/{id}/audio', name: 'web_post_audio_stream', methods: ['GET'])]
    public function streamAudio(Page $page, Request $request): Response
    {
        if ($page->getStatus() !== EditorialStatus::PUBLISHED) {
            return new Response('Audio file not found.', 404);
        }

        $audio = $this->audioRepository->findOneBy(['page' => $page]);
        if (null === $audio) {
            return new Response('Audio file not found.', 404);
        }

        $filePath = $this->storageProvider->getFullPath($audio);
        if (!file_exists($filePath)) {
            return new Response('Audio file not found on disk.', 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', $audio->getFiletype());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $audio->getFilename()
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
