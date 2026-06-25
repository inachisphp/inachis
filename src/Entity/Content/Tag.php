<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Content;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Object for handling tags that are mapped to content.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\Content\TagRepository', readOnly: false)]
#[ORM\Index(name: "search_idx", columns: [ "title" ])]
class Tag
{
    /**
     * @var UuidInterface|null The unique identifier for the tag
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string The text for the tag
     */
    #[ORM\Column(type: "string", length: 50, unique: true)]
    protected string $title;

    /**
     * @var string The slug for the tag
     */
    #[ORM\Column(type: "string", length: 60, unique: true)]
    protected string $slug;

    /**
     * Initialises a new {@link Tag}.
     *
     * @param string $title The value of the tag
     */
    public function __construct(string $title)
    {
        $this->setTitle($title);
    }

    /**
     * Gets the unique identifier of the tag.
     *
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Gets the title of the tag.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Gets the slug for the tag.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Sets the title of the tag and generates a slug.
     *
     * @param string $value The value of the tag
     * @return self
     */
    public function setTitle(string $value): self
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Tag title cannot be empty');
        }

        $this->title = mb_strtolower($value);
        $this->slug = $this->slugify($value);
        return $this;
    }

    /**
     * Generates a slug for a string.
     *
     * @param string $value The value to slugify
     * @return string The slug
     */
    private function slugify(string $value): string
    {
        $slugger = new AsciiSlugger();
        $slug = $slugger
            ->slug($value)
            ->lower()
            ->toString();
        return trim($slug, '-');
    }

    /**
     * Provides simple Tag to string conversion
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }
}
