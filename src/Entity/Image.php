<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Object for handling images on a site.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\ImageRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
#[ORM\Index(columns: ['title', 'alt_text', 'description'], name: "fulltext_title_content", flags: ["fulltext"])]
class Image extends AbstractFile
{
    /**
     * @const string RegExp for allowed mime-types
     */
    public const ALLOWED_MIME_TYPES = 'image\/(png|p?jpeg|hei[cf]|webp|svg+xml)';
    public const ALLOWED_TYPES = '.jpg,.jpeg,.png,.heic,.heif,.webp,.svg';

    public const WARNING_DIMENSIONS = 2048;
    public const WARNING_FILESIZE = 2048; //kb

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected int $dimensionX = 0;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected int $dimensionY = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $altText = '';

    /**
     * Default constructor for {@link Image}.
     */
    public function __construct()
    {
        $now = new DateTime();
        $this->setCreateDate($now);
        $this->setModDate($now);
        unset($now);
    }

    /**
     * @return int
     */
    public function getDimensionX(): int
    {
        return $this->dimensionX;
    }

    /**
     * @return int
     */
    public function getDimensionY(): int
    {
        return $this->dimensionY;
    }

    /**
     * @return string|null
     */
    public function getAltText(): ?string
    {
        return $this->altText;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setDimensionX(int $value): self
    {
        $this->dimensionX = $value;

        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setDimensionY(int $value): self
    {
        $this->dimensionY = $value;

        return $this;
    }

    /**
     * @param string|null $value
     * @return $this
     */
    public function setAltText(?string $value): self
    {
        $this->altText = $value;

        return $this;
    }

    /**
     * @param $imageDirectory
     * @return array
     */
    public function getImageProperties($imageDirectory): array
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
