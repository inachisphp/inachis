<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Resource;

use Inachis\Entity\Media\DownloadVersion;
use Inachis\Repository\Media\DownloadRepository;
use Inachis\Service\Resource\ResourceStorageProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class ResourceWebController extends AbstractController
{
    #[Route('/download/{id}/{version}', name: 'web_file_download', defaults: ['version' => null], methods: ['GET'])]
    public function streamDownload(
        string $id,
        ?int $version,
        DownloadRepository $downloadRepository,
        ResourceStorageProvider $storageProvider,
    ): Response {
        $download = $downloadRepository->find($id);

        if (!$download) {
            throw $this->createNotFoundException('Requested file does not exist.');
        }

        $filename = $download->getFilename();

        // Handle requesting a historic version
        if (null !== $version) {
            $historic = $download->getVersions()->filter(
                fn (DownloadVersion $v) => $v->getVersionNumber() === $version,
            )->first();

            if (!$historic) {
                throw $this->createNotFoundException('Requested version does not exist.');
            }

            $filename = $historic->getFilename();
        }

        $filePath = $storageProvider->getStorageDirectory('downloads').basename($filename);

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw $this->createNotFoundException('File payload not found on disk.');
        }

        $response = new BinaryFileResponse($filePath);

        $disposition = match ($download->getFiletype()) {
            'application/pdf' => ResponseHeaderBag::DISPOSITION_INLINE,
            default => ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        };

        $response->setContentDisposition($disposition, $filename);
        $response->headers->set('Cache-Control', 'private, no-transform, max-age=3600');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
