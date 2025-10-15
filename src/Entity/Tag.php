<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling tags that are mapped to content.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\TagRepository', readOnly: false)]
#[ORM\Index(columns: [ "title" ], name: "search_idx")]
class Tag
{
    /**
     * @var UuidInterface|null The unique identifier for the tag
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string The text for the tag
     */
    #[ORM\Column(type: "string", length: 50)]
    protected string $title;

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
     * @param UuidInterface $value
     * @return $this
     */
    public function setId(UuidInterface $value): self
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
