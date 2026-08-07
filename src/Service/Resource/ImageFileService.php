<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Resource;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Transformer\ImageTransformer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

readonly class ImageFileService
{
    public function __construct(
        private ImageTransformer $transformer,
        private SluggerInterface $slugger,
        private ResourceStorageProvider $storageProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Create a SHA-256 checksum hash of the uploaded image.
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
     * Uses getimagesize to get dimensions of the uploaded image.
     *
     * @return array<int|string, int|string>|false
     */
    public function getImageDimensions(UploadedFile $file): array|false
    {
        return getimagesize($file->getRealPath());
    }

    /**
     * Optimise image: resize, compress, convert to WebP/AVIF.
     */
    public function optimise(UploadedFile $file): UploadedFile
    {
        if (!extension_loaded('imagick')) {
            return $file;
        }

        $file = $this->convertHEICToJPEG($file);

        $sourcePath = $file->getRealPath();
        if (false === $sourcePath) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $destinationPath = tempnam(sys_get_temp_dir(), 'opt_');
        if (false === $destinationPath) {
            throw new \RuntimeException('Unable to create temp file.');
        }

        $maxWidth = $maxHeight = Image::WARNING_DIMENSIONS;
        $this->transformer->optimiseImage(
            $sourcePath,
            $destinationPath,
            $maxWidth,
            $maxHeight,
        );

        $extension = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['webp', 'avif'], true)) {
            $extension = '.webp';
            rename($destinationPath, $destinationPath.$extension);
            $destinationPath .= $extension;
        }

        return new UploadedFile(
            $destinationPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).$extension,
            mime_content_type($destinationPath) ?: null,
            null,
            true,
        );
    }

    /**
     * Convert HEIC to JPEG if needed.
     */
    public function convertHEICToJPEG(UploadedFile $file): UploadedFile
    {
        if (!$this->transformer->isHEICSupported()) {
            return $file;
        }
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['heic', 'heif'], true)) {
            return $file;
        }
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/heic', 'image/heif'], true)) {
            return $file;
        }

        $sourcePath = $file->getRealPath();
        if (false === $sourcePath) {
            throw new \RuntimeException('Unable to determine file path.');
        }

        $destinationPath = tempnam(sys_get_temp_dir(), 'heic_').'.jpg';

        try {
            $this->transformer->convertHeicToJpeg(
                $sourcePath,
                $destinationPath,
                85,
            );
        } catch (\ImagickException $e) {
            unlink($destinationPath);
            throw new \RuntimeException('HEIC conversion failed.', 0, $e);
        }

        return new UploadedFile(
            $destinationPath,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg',
            'image/jpeg',
            null,
            true,
        );
    }

    /**
     * Processes an uploaded image file, stores it on disk, and creates a persisted Image entity.
     */
    public function createFromUpload(
        UploadedFile $uploadedFileInput,
        string $title,
        ?string $description = null,
        ?string $altText = null,
        bool $optimise = false,
        ?User $author = null,
    ): Image {
        // Step 1: Convert HEIC to JPEG if required
        $uploadedFile = $this->convertHEICToJPEG($uploadedFileInput);

        // Step 2: Optimise if required
        if ($optimise) {
            $uploadedFile = $this->optimise($uploadedFile);
        }

        // Step 3: Extract dimensions
        $dimensions = $this->getImageDimensions($uploadedFile);
        if (false === $dimensions) {
            throw new \RuntimeException('Unable to read image dimensions.');
        }

        // Step 4: Checksum & duplicate check
        $checksum = $this->createChecksum($uploadedFile);
        $existingImage = $this->entityManager->getRepository(Image::class)->findOneBy([
            'checksum' => $checksum,
        ]);
        if ($existingImage) {
            throw new \RuntimeException('Duplicate image found.');
        }

        // Step 5: Generate filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanTitle = '' !== trim($title) ? $title : $originalFilename;

        $safeFilename = strtolower((string) $this->slugger->slug($cleanTitle.'-'.uniqid()));
        $newFilename = $safeFilename.'.'.$uploadedFile->guessExtension();

        $imageSize = $uploadedFile->getSize();
        $imageMimeType = $uploadedFile->getMimeType();

        // Step 6: Move file
        $storageDir = $this->storageProvider->getStorageDirectory(Image::class);
        $uploadedFile->move($storageDir, $newFilename);

        // Step 7: Persist Entity
        $image = new Image();
        $image
            ->setTitle($title)
            ->setDescription($description)
            ->setAltText($altText)
            ->setFilesize($imageSize)
            ->setFiletype($imageMimeType ?? '')
            ->setFilename($newFilename)
            ->setChecksum($checksum)
            ->setDimensionX((int) $dimensions[0])
            ->setDimensionY((int) $dimensions[1])
            ->setAuthor($author);

        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $image;
    }
}
