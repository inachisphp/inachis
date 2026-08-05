<?php

declare(strict_types=1);

namespace Inachis\Service\File;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DownloadFileService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function createChecksum(UploadedFile $file): string
    {
        $hash = hash_file('sha256', $file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Failed to generate file checksum.');
        }

        return $hash;
    }

    public function storeFile(UploadedFile $uploadedFile, string $targetDirectory, string $title): array
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanTitle = '' !== trim($title) ? $title : $originalFilename;

        // Path Traversal Security: Force strict slugging and safe extensions
        $safeFilename = strtolower((string) $this->slugger->slug($cleanTitle . '-' . uniqid()));
        $extension = $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();
        $newFilename = sprintf('%s.%s', $safeFilename, strtolower($extension));

        $size = $uploadedFile->getSize();
        $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';
        $checksum = $this->createChecksum($uploadedFile);

        try {
            $uploadedFile->move($targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to save file to secure storage: ' . $e->getMessage());
        }

        return [
            'filename' => $newFilename,
            'filesize' => $size,
            'filetype' => $mimeType,
            'checksum' => $checksum,
        ];
    }
}
