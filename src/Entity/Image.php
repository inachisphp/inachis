<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * Object for handling images on a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\ImageRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['title', 'filename', 'filetype'])]
class Image extends AbstractFile
{
    /**
     * @const string RegExp for allowed mime-types
     */
    const ALLOWED_TYPES = 'image\/(png|p?jpeg|gif)';

    /**
     * @var \Ramsey\Uuid\UuidInterface The unique identifier for the image
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected $dimensionX = 0;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected $dimensionY = 0;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', nullable: true)]
    protected $altText;

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
     * @return string
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
        $this->dimensionX = (int) $value;

        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setDimensionY(int $value): self
    {
        $this->dimensionY = (int) $value;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setAltText(?string $value): self
    {
        $this->altText = $value;

        return $this;
    }
}
