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

/**
 * Handles creation, storage and replacement of audio resources.
 */
readonly class AudioService
{
    public function __construct(
        private AudioRepository $audioRepository,
        private SluggerInterface $slugger,
        private ResourceStorageProvider $storageProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Create a SHA-256 checksum for an uploaded audio file.
     */
    public function createChecksum(UploadedFile $file): string
    {
        $checksum = hash_file('sha256', $file->getPathname());

        if (false === $checksum) {
            throw new \RuntimeException(
                sprintf('Unable to calculate checksum for "%s".', $file->getClientOriginalName()),
            );
        }

        return $checksum;
    }

    /**
     * Store an uploaded audio file.
     *
     * @return array{
     *     filename: string,
     *     filesize: int,
     *     filetype: string,
     *     checksum: string
     * }
     */
    public function storeFile(UploadedFile $file, string $title): array
    {
        $storageDirectory = $this->storageProvider->getStorageDirectory(Audio::class);

        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0775, true) && !is_dir($storageDirectory)) {
            throw new \RuntimeException(
                sprintf('Unable to create audio storage directory "%s".', $storageDirectory),
            );
        }

        $originalFilename = $file->getClientOriginalName();

        if ('' === $originalFilename) {
            $originalFilename = $title;
        }

        $extension = $file->guessExtension();

        if (null === $extension || '' === $extension) {
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        }

        if ('' === $extension) {
            throw new \RuntimeException(
                sprintf('Unable to determine the file extension for "%s".', $originalFilename),
            );
        }

        $slug = (string) $this->slugger->slug($title)->lower();

        if ('' === $slug) {
            $slug = 'audio';
        }

        /*
         * Use a random component rather than the original filename to avoid
         * collisions and prevent user-controlled filenames from determining
         * the physical storage path.
         */
        $filename = sprintf(
            '%s-%s.%s',
            $slug,
            bin2hex(random_bytes(16)),
            strtolower($extension),
        );

        $filesize = $file->getSize();

        if (false === $filesize) {
            throw new \RuntimeException(
                sprintf('Unable to determine the size of "%s".', $originalFilename),
            );
        }

        $filetype = $file->getMimeType();

        if (null === $filetype || '' === $filetype) {
            $filetype = $file->getClientMimeType();
        }

        if (null === $filetype || '' === $filetype) {
            throw new \RuntimeException(
                sprintf('Unable to determine the MIME type for "%s".', $originalFilename),
            );
        }

        $checksum = $this->createChecksum($file);

        try {
            $file->move($storageDirectory, $filename);
        } catch (FileException $exception) {
            throw new \RuntimeException(
                sprintf('Unable to store audio file "%s".', $originalFilename),
                previous: $exception,
            );
        }

        return [
            'filename' => $filename,
            'filesize' => $filesize,
            'filetype' => $filetype,
            'checksum' => $checksum,
        ];
    }

    /**
     * Create a new local audio resource from an upload.
     */
    public function createFromUpload(
        UploadedFile $file,
        string $title,
        ?string $description = null,
        ?User $author = null,
        ?int $duration = null,
    ): Audio {
        $checksum = $this->createChecksum($file);

        $existing = $this->audioRepository->findOneBy([
            'checksum' => $checksum,
        ]);

        if (null !== $existing) {
            throw new \RuntimeException(
                sprintf('An audio file with checksum "%s" already exists.', $checksum),
            );
        }

        $stored = $this->storeFile($file, $title);

        $audio = new Audio();
        $audio
            ->setTitle($title)
            ->setDescription($description)
            ->setFilename($stored['filename'])
            ->setFilesize($stored['filesize'])
            ->setFiletype($stored['filetype'])
            ->setChecksum($stored['checksum'])
            ->setDuration($duration)
            ->setStorage(AudioStorage::LOCAL)
            ->setExternalUrl(null)
            ->setSourceHash(null)
            ->setAuthor($author);

        $this->entityManager->persist($audio);
        $this->entityManager->flush();

        return $audio;
    }

    /**
     * Replace the current audio file with a new local upload.
     *
     * The current resource is first recorded as an AudioVersion.
     */
    public function replaceFile(
        Audio $audio,
        UploadedFile $file,
        ?User $author = null,
        ?int $duration = null,
    ): Audio {
        $checksum = $this->createChecksum($file);

        if ($checksum === $audio->getChecksum()) {
            throw new \InvalidArgumentException(
                'The uploaded audio file is identical to the current file.',
            );
        }

        $existing = $this->audioRepository->findOneBy([
            'checksum' => $checksum,
        ]);

        if (null !== $existing && $existing !== $audio) {
            throw new \RuntimeException(
                sprintf('An audio file with checksum "%s" already exists.', $checksum),
            );
        }

        $this->archiveCurrentVersion($audio, $author);

        $stored = $this->storeFile($file, $audio->getTitle());

        $audio
            ->setFilename($stored['filename'])
            ->setFilesize($stored['filesize'])
            ->setFiletype($stored['filetype'])
            ->setChecksum($stored['checksum'])
            ->setDuration($duration)
            ->setStorage(AudioStorage::LOCAL)
            ->setExternalUrl(null)
            ->setSourceHash(null);

        return $audio;
    }

    /**
     * Replace the current audio resource with an externally hosted resource.
     *
     * Existing metadata is retained unless explicitly supplied. This is
     * useful when moving an existing local recording to another host while
     * retaining its known metadata.
     */
    public function replaceWithExternal(
        Audio $audio,
        string $externalUrl,
        ?User $author = null,
        ?string $filetype = null,
        ?int $filesize = null,
        ?string $checksum = null,
        ?int $duration = null,
        ?string $sourceHash = null,
    ): Audio {
        $externalUrl = trim($externalUrl);

        if ('' === $externalUrl || false === filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('The supplied external audio URL is invalid.');
        }

        if (
            $audio->isExternal()
            && $audio->getExternalUrl() === $externalUrl
        ) {
            return $audio;
        }

        $this->archiveCurrentVersion($audio, $author);

        $audio
            ->setStorage(AudioStorage::EXTERNAL)
            ->setExternalUrl($externalUrl)
            ->setFiletype($filetype ?? $audio->getFiletype())
            ->setFilesize($filesize ?? $audio->getFilesize())
            ->setChecksum($checksum ?? $audio->getChecksum())
            ->setDuration($duration ?? $audio->getDuration())
            ->setSourceHash($sourceHash);

        return $audio;
    }

    /**
     * Archive the current state of an audio resource.
     *
     * This stores the metadata snapshot. Physical local-file retention is
     * deliberately separate from the version metadata.
     */
    private function archiveCurrentVersion(
        Audio $audio,
        ?User $author = null,
    ): AudioVersion {
        $version = new AudioVersion();

        $version
            ->setAudio($audio)
            ->setVersionNumber($audio->getNextVersionNumber())
            ->setFilename($audio->getFilename())
            ->setFiletype($audio->getFiletype())
            ->setFilesize($audio->getFilesize())
            ->setChecksum($audio->getChecksum())
            ->setDuration($audio->getDuration())
            ->setStorage($audio->getStorage())
            ->setExternalUrl($audio->getExternalUrl())
            ->setSourceHash($audio->getSourceHash())
            ->setAuthor($author ?? $audio->getAuthor());

        $audio->addVersion($version);

        $this->entityManager->persist($version);

        return $version;
    }
}
