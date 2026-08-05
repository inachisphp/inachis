<?php

declare(strict_types=1);

namespace Inachis\Controller\Page\Resource;

use Inachis\Repository\Media\DownloadRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

class ResourceWebController extends AbstractController
{
    #[Route('/download/{id}', name: 'web_file_download', methods: ['GET'])]
    public function streamDownload(
        string $id,
        DownloadRepository $downloadRepository,
        #[Autowire('%kernel.project_dir%/var/uploads/')] string $uploadDirectory
    ): Response {
        $download = $downloadRepository->find($id);

        if (!$download) {
            throw $this->createNotFoundException('Requested file does not exist.');
        }

        // Clean path sanitization to prevent path traversal
        $filename = basename($download->getFilename());
        $filePath = $uploadDirectory . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw $this->createNotFoundException('File payload not found on disk.');
        }

        // BinaryFileResponse handles 206 Partial Content (Range requests) automatically
        $response = new BinaryFileResponse($filePath);
        
        // Prevent inline execution of dangerous uploaded HTML/JS scripts
        $disposition = match ($download->getFiletype()) {
            'application/pdf' => ResponseHeaderBag::DISPOSITION_INLINE,
            default => ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        };

        $response->setContentDisposition(
            $disposition,
            $download->getFilename()
        );

        // Security headers against hotlinking/abuse
        $response->headers->set('Cache-Control', 'private, no-transform, max-age=3600');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}