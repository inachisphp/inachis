<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Media;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\Media\AbstractFile;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

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
 *    createDate: string,
 *    modDate: string,
 *    dimensionX: int,
 *    dimensionY: int,
 *    altText?: string
 * }
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\Media\ImageRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
#[ORM\Index(columns: ['title', 'alt_text', 'description'], name: "fulltext_title_content", flags: ["fulltext"])]
class Image extends AbstractFile
{
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/heic', 'image/heif', 'image/webp', 'image/svg+xml'];

    /** @var list<string> */
    public const ALLOWED_TYPES = ['.jpg', '.jpeg', '.png', '.heic', '.heif', '.webp', '.svg'];

    public const WARNING_DIMENSIONS = 2048;
    public const WARNING_FILESIZE = 2048; //kb

    /** @var int The width of the image */
    #[ORM\Column(type: 'integer')]
    protected int $dimensionX = 0;

    /** @var int The height of the image */
    #[ORM\Column(type: 'integer')]
    protected int $dimensionY = 0;

    /** @var string|null The alt text for the image */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $altText = '';

    /**
     * Default constructor for {@link Image}.
     */
    public function __construct()
    {
        $now = new DateTimeImmutable();
        $this->setCreateDate($now);
        $this->setModDate($now);
        unset($now);
    }

    /**
     * Returns the width of the image
     * 
     * @return int
     */
    public function getDimensionX(): int
    {
        return $this->dimensionX;
    }

    /**
     * Returns the height of the image
     * 
     * @return int
     */
    public function getDimensionY(): int
    {
        return $this->dimensionY;
    }

    /**
     * Returns alt text for the image
     * 
     * @return string|null
     */
    public function getAltText(): ?string
    {
        return $this->altText;
    }

    /**
     * Sets the width of the image
     * 
     * @param int $value
     * @return self
     */
    public function setDimensionX(int $value): self
    {
        $this->dimensionX = $value;

        return $this;
    }

    /**
     * Sets the height of the image
     * 
     * @param int $value
     * @return self
     */
    public function setDimensionY(int $value): self
    {
        $this->dimensionY = $value;

        return $this;
    }

    /**
     * Sets the alt text for the image
     * 
     * @param string|null $value
     * @return self
     */
    public function setAltText(?string $value): self
    {
        $this->altText = $value;

        return $this;
    }

    /**
     * Gets the properties of the image file using PHP's getimagesize function
     * 
     * @param string $imageDirectory
     * @return array<int|string, int|string>|false
     */
    public function getImageProperties(string $imageDirectory): array|false
    {
        $fullImagePath = self::getFilename();
        if (!empty($imageDirectory) && !str_starts_with($fullImagePath, 'http')) {
            $fullImagePath = $imageDirectory . $fullImagePath;
        }
        if (!file_exists($fullImagePath) || !is_file($fullImagePath)) {
            throw new FileNotFoundException();
        }
        return getimagesize($fullImagePath);
    }
}
