<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Resource;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Media\Download;
use Inachis\Entity\User\User;
use Inachis\Repository\Media\DownloadRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DownloadFileService
{
    public function __construct(
        private DownloadRepository $downloadRepository,
        private readonly SluggerInterface $slugger,
        private ResourceStorageProvider $storageProvider,
        private EntityManagerInterface $entityManager,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function createChecksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if (false === $path) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $hash = hash_file('sha256', $path);
        if (false === $hash) {
            throw new \RuntimeException('Failed to generate file checksum.');
        }

        return $hash;
    }

    /**
     * Moves an uploaded file to storage and returns its metadata.
     *
     * @return array{filename: string, filesize: int, filetype: string, checksum: string}
     */
    public function storeFile(UploadedFile $uploadedFile, string $title): array
    {
        $targetDirectory = $this->storageProvider->getStorageDirectory(Download::class);
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanTitle = '' !== trim($title) ? $title : $originalFilename;

        $safeFilename = strtolower((string) $this->slugger->slug($cleanTitle.'-'.uniqid()));
        $extension = $uploadedFile->guessExtension() ?? $uploadedFile->getClientOriginalExtension();
        $newFilename = sprintf('%s.%s', $safeFilename, strtolower($extension));

        $size = $uploadedFile->getSize();
        $mimeType = $uploadedFile->getMimeType() ?? 'application/octet-stream';
        $checksum = $this->createChecksum($uploadedFile);

        try {
            $uploadedFile->move($targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to save file to secure storage: '.$e->getMessage());
        }

        return [
            'filename' => $newFilename,
            'filesize' => $size,
            'filetype' => $mimeType,
            'checksum' => $checksum,
        ];
    }

    /**
     * Creates, stores, and persists a new Download entity.
     */
    public function createFromUpload(
        UploadedFile $file,
        string $title,
        ?string $description = null,
        ?User $author = null,
    ): Download {
        $checksum = $this->createChecksum($file);
        $existing = $this->downloadRepository->findOneBy([
            'checksum' => $checksum,
        ]);
        if ($existing) {
            throw new \RuntimeException('Duplicate file found.');
        }

        $fileMeta = $this->storeFile($file, $title);

        $download = new Download();
        $download
            ->setTitle($title)
            ->setDescription($description)
            ->setFilename($fileMeta['filename'])
            ->setFilesize($fileMeta['filesize'])
            ->setFiletype($fileMeta['filetype'])
            ->setChecksum($fileMeta['checksum'])
            ->setAuthor($author);

        $this->entityManager->persist($download);
        $this->entityManager->flush();

        return $download;
    }

    /**
     * Replaces the active binary file for a Download while archiving the previous version.
     */
    public function replaceFile(Download $download, UploadedFile $file): Download
    {
        if (null !== $download->getId() && !empty($download->getFilename())) {
            $download->archiveCurrentPayload();
        }

        $fileMeta = $this->storeFile($file, $download->getTitle());

        $download
            ->setFilename($fileMeta['filename'])
            ->setFilesize($fileMeta['filesize'])
            ->setFiletype($fileMeta['filetype'])
            ->setChecksum($fileMeta['checksum']);

        return $download;
    }
}
