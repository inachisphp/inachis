<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling images on a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\ImageRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
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
     * @var UuidInterface The unique identifier for the image
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected UuidInterface $id;

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
        $now = new \DateTime();
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
     * @return ?string
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
     * @param ?string $value
     * @return $this
     */
    public function setAltText(?string $value): self
    {
        $this->altText = $value;

        return $this;
    }
}
