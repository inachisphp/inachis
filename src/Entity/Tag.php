<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * Object for handling tags that are mapped to content.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\TagRepository', readOnly: false)]
#[ORM\Index(name: "search_idx", columns: [ "title" ])]
class Tag
{
    /**
     * @var \Ramsey\Uuid\UuidInterface The unique identifier for the tag
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var string The text for the tag
     */
    #[ORM\Column(type: "string", length: 50)]
    protected $title;

    public function __construct(string $title = '')
    {
        $this->setTitle($title);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setId(string $value): self
    {
        $this->id = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setTitle(string $value): self
    {
        $this->title = $value;
        return $this;
    }
}
