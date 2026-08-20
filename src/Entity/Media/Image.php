<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Object for handling images on a site.
 *
 * @phpstan-type ImageShape array{
 *    id: string,
 *    title?: string,
 *    description?: string,
 *    filename: string,
 *    filetype: string,
 *    filesize: int,
 *    checksum: string,
 *    author?: string,
 *    createdAt: string,
 *    updatedAt: string,
 *    dimensionX: int,
 *    dimensionY: int,
 *    altText?: string
 * }
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\Media\ImageRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
#[ORM\Index(columns: ['title', 'alt_text', 'description'], name: 'fulltext_title_content', flags: ['fulltext'])]
#[ORM\HasLifecycleCallbacks]
class Image extends AbstractFile
{
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/heic', 'image/heif', 'image/webp', 'image/svg+xml'];

    /** @var list<string> */
    public const ALLOWED_TYPES = ['.jpg', '.jpeg', '.png', '.heic', '.heif', '.webp', '.svg'];

    public const WARNING_DIMENSIONS = 2048;
    public const WARNING_FILESIZE = 2048; // kb

    /** @var int The width of the image */
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer')]
    protected int $dimensionX = 0;

    /** @var int The height of the image */
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer')]
    protected int $dimensionY = 0;

    /** @var string|null The alt text for the image */
    #[Assert\Length(max: 255)]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $altText = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Returns the width of the image.
     */
    public function getDimensionX(): int
    {
        return $this->dimensionX;
    }

    /**
     * Returns the height of the image.
     */
    public function getDimensionY(): int
    {
        return $this->dimensionY;
    }

    /**
     * Returns alt text for the image.
     */
    public function getAltText(): ?string
    {
        return $this->altText;
    }

    /**
     * Sets the width of the image.
     */
    public function setDimensionX(int $value): self
    {
        $this->dimensionX = $value;

        return $this;
    }

    /**
     * Sets the height of the image.
     */
    public function setDimensionY(int $value): self
    {
        $this->dimensionY = $value;

        return $this;
    }

    /**
     * Sets the alt text for the image.
     */
    public function setAltText(?string $value): self
    {
        $this->altText = $value;

        return $this;
    }

    /**
     * Gets the properties of the image file using PHP's getimagesize function.
     *
     * @return array<int|string, int|string>|false
     */
    public function getImageProperties(string $imageDirectory): array|false
    {
        $fullImagePath = self::getFilename();
        if (!empty($imageDirectory) && !str_starts_with($fullImagePath, 'http')) {
            $fullImagePath = $imageDirectory.$fullImagePath;
        }
        if (!file_exists($fullImagePath) || !is_file($fullImagePath)) {
            throw new FileNotFoundException();
        }

        return getimagesize($fullImagePath);
    }

    /**
     * Returns the dimensions fo the image as a string (w x h).
     */
    public function getDimensionsString(): string
    {
        return $this->dimensionX.' x '.$this->dimensionY;
    }
}
