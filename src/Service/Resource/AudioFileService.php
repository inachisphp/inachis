<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Resource;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Media\Audio;
use Inachis\Entity\Media\AudioVersion;
use Inachis\Entity\User\User;
use Inachis\Enum\Media\AudioStorage;
use Inachis\Repository\Media\AudioRepository;
use Inachis\Service\Resource\ResourceStorageProvider;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class AudioFileService
{
    public function __construct(
        private readonly AudioRepository $audioRepository,
        private readonly SluggerInterface $slugger,
        private readonly ResourceStorageProvider $storageProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Creates a SHA-256 checksum hash of an uploaded audio file.
     */
    public function createChecksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if (false === $path) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $hash = hash_file('sha256', $path);

        if (false === $hash) {
            throw new \RuntimeException('Unable to generate checksum.');
        }

        return $hash;
    }

    /**
     * Gets the duration of an audio file.
     *
     * This currently relies on ffprobe being available on the system.
     * If ffprobe is unavailable, null is returned.
     */
    public function getDuration(UploadedFile $file): ?int
    {
        $path = $file->getRealPath();

        if (false === $path || !is_file($path)) {
            throw new \RuntimeException('Unable to determine audio file path.');
        }

        $ffprobe = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));

        if ('' === $ffprobe) {
            return null;
        }

        $command = sprintf(
            '%s -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
            escapeshellarg($ffprobe),
            escapeshellarg($path),
        );

        $output = trim((string) shell_exec($command));

        if ('' === $output || !is_numeric($output)) {
            return null;
        }

        return max(0, (int) round((float) $output));
    }

    /**
     * Moves an uploaded audio file to storage and returns its metadata.
     *
     * @return array{
     *     filename: string,
     *     filesize: int,
     *     filetype: string,
     *     checksum: string,
     *     duration: ?int
     * }
     */
    public function storeFile(
        UploadedFile $uploadedFile,
        string $title,
    ): array {
        $targetDirectory = $this->storageProvider->getStorageDirectory(Audio::class);

        $originalFilename = pathinfo(
            $uploadedFile->getClientOriginalName(),
            PATHINFO_FILENAME,
        );

        $cleanTitle = '' !== trim($title)
            ? $title
            : $originalFilename;

        $safeFilename = strtolower(
            (string) $this->slugger->slug($cleanTitle.'-'.uniqid()),
        );

        $extension = $uploadedFile->guessExtension()
            ?? $uploadedFile->getClientOriginalExtension();

        $extension = strtolower(ltrim($extension, '.'));

        if ('' === $extension) {
            throw new \RuntimeException('Unable to determine audio file extension.');
        }

        $newFilename = sprintf('%s.%s', $safeFilename, $extension);

        $filesize = $uploadedFile->getSize();

        if (false === $filesize) {
            throw new \RuntimeException('Unable to determine audio file size.');
        }

        $filetype = $uploadedFile->getMimeType()
            ?? $uploadedFile->getClientMimeType();

        if (null === $filetype || '' === $filetype) {
            throw new \RuntimeException('Unable to determine audio file MIME type.');
        }

        $checksum = $this->createChecksum($uploadedFile);
        $duration = $this->getDuration($uploadedFile);

        try {
            $uploadedFile->move($targetDirectory, $newFilename);
        } catch (FileException $e) {
            throw new \RuntimeException(
                'Failed to save audio file to secure storage: '.$e->getMessage(),
                0,
                $e,
            );
        }

        return [
            'filename' => $newFilename,
            'filesize' => $filesize,
            'filetype' => $filetype,
            'checksum' => $checksum,
            'duration' => $duration,
        ];
    }

    /**
     * Creates, stores, and persists a new Audio entity from an upload.
     */
    public function createFromUpload(
        UploadedFile $file,
        string $title,
        ?string $description = null,
        ?User $author = null,
    ): Audio {
        $checksum = $this->createChecksum($file);

        $existing = $this->audioRepository->findOneBy([
            'checksum' => $checksum,
        ]);

        if (null !== $existing) {
            throw new \RuntimeException('Duplicate audio file found.');
        }

        $fileMeta = $this->storeFile($file, $title);

        $audio = new Audio();

        $audio
            ->setTitle($title)
            ->setDescription($description)
            ->setFilename($fileMeta['filename'])
            ->setFilesize($fileMeta['filesize'])
            ->setFiletype($fileMeta['filetype'])
            ->setChecksum($fileMeta['checksum'])
            ->setDuration($fileMeta['duration'])
            ->setAuthor($author);

        $this->entityManager->persist($audio);
        $this->entityManager->flush();

        return $audio;
    }

    /**
     * Creates an externally hosted audio resource.
     *
     * No local file is created or stored.
     */
    public function createFromExternalUrl(
        string $url,
        string $title,
        ?string $description = null,
        ?User $author = null,
        ?int $duration = null,
    ): Audio {
        $audio = new Audio();

        $audio
            ->setTitle($title)
            ->setDescription($description)
            ->setStorage(\Inachis\Enum\Media\AudioStorage::EXTERNAL)
            ->setExternalUrl($url)
            ->setDuration($duration)
            ->setAuthor($author);

        $this->entityManager->persist($audio);
        $this->entityManager->flush();

        return $audio;
    }

    /**
     * Replaces the active local audio file and archives the previous
     * resource as an AudioVersion.
     */
    public function replaceFile(
        Audio $audio,
        UploadedFile $file,
        ?User $author = null,
    ): Audio {
        $this->archiveCurrentVersion($audio, $author);

        $checksum = $this->createChecksum($file);

        $existing = $this->audioRepository->findOneBy([
            'checksum' => $checksum,
        ]);

        if (null !== $existing && $existing->getId() !== $audio->getId()) {
            throw new \RuntimeException('Duplicate audio file found.');
        }

        $title = $audio->getTitle() ?? '';
        $fileMeta = $this->storeFile($file, $title);

        $audio
            ->setFilename($fileMeta['filename'])
            ->setFilesize($fileMeta['filesize'])
            ->setFiletype($fileMeta['filetype'])
            ->setChecksum($fileMeta['checksum'])
            ->setDuration($fileMeta['duration'])
            ->setStorage(AudioStorage::LOCAL)
            ->setExternalUrl(null);

        $this->entityManager->flush();

        return $audio;
    }

    /**
     * Replaces the active audio with an externally hosted resource.
     *
     * The previous local file or external URL is archived first.
     */
    public function replaceWithExternalUrl(
        Audio $audio,
        string $url,
        ?int $duration = null,
        ?User $author = null,
    ): Audio {
        $this->archiveCurrentVersion($audio, $author);

        $audio
            ->setStorage(AudioStorage::EXTERNAL)
            ->setExternalUrl($url)
            ->setFilename('')
            ->setFilesize(0)
            ->setFiletype('')
            ->setChecksum('')
            ->setDuration($duration);

        $this->entityManager->flush();

        return $audio;
    }

    /**
     * Archives the current active audio resource as a version.
     *
     * Nothing is archived if the Audio entity does not yet contain a
     * meaningful resource.
     */
    public function archiveCurrentVersion(
        Audio $audio,
        ?User $author = null,
    ): ?AudioVersion {
        $hasLocalFile = '' !== $audio->getFilename();
        $hasExternalUrl = null !== $audio->getExternalUrl();

        if (!$hasLocalFile && !$hasExternalUrl) {
            return null;
        }

        $version = new AudioVersion();

        $version
            ->setAudio($audio)
            ->setVersionNumber($audio->getNextVersionNumber())
            ->setFilename($audio->getFilename())
            ->setFilesize($audio->getFilesize())
            ->setFiletype($audio->getFiletype())
            ->setChecksum($audio->getChecksum())
            ->setDuration($audio->getDuration())
            ->setStorage($audio->getStorage())
            ->setExternalUrl($audio->getExternalUrl())
            ->setSourceHash($audio->getSourceHash())
            ->setAuthor($author);

        $audio->addVersion($version);

        $this->entityManager->persist($version);

        return $version;
    }

    /**
     * Returns the absolute path to a locally stored audio file.
     */
    public function getFullPath(Audio $audio): string
    {
        if (!$audio->isLocal()) {
            throw new \RuntimeException(
                'External audio resources do not have a local file path.',
            );
        }

        if ('' === $audio->getFilename()) {
            throw new \RuntimeException(
                'Audio resource does not have a local filename.',
            );
        }

        return $this->storageProvider->getFullPath($audio);
    }

    /**
     * Determines whether the audio has a usable resource.
     */
    public function hasResource(Audio $audio): bool
    {
        if ($audio->isExternal()) {
            return null !== $audio->getExternalUrl()
                && '' !== trim($audio->getExternalUrl());
        }

        return '' !== trim($audio->getFilename());
    }
}
