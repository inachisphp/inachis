<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\System;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Entity for storing navigation tabs for the website.
 */
#[ORM\Entity]
#[UniqueEntity('position')]
class NavigationTab
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    /**
     * The title for the tab.
     */
    #[ORM\Column(length: 100)]
    private string $title;

    /**
     * The URL for the tab.
     */
    #[ORM\Column(length: 255)]
    private string $url;

    /**
     * The position of the tab.
     */
    #[ORM\Column(unique: true)]
    private int $position = 0;

    /** @var bool Flag indicating if the tab is active */
    #[ORM\Column]
    private bool $isActive = true;

    /** @var bool Flag indicating if this is a system-defined tab that cannot be removed */
    #[ORM\Column]
    private bool $isSystem = false;

    /**
     * Set the value of id.
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of id.
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Get the value of title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the value of title.
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of url.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the value of url.
     */
    public function setUrl(string $url): self
    {
        if ($this->isSystem()) {
            return $this;
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set the value of position.
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the value of isActive.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set the value of isActive.
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get the value of isSystem.
     */
    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    /**
     * Set the value of isSystem.
     */
    public function setIsSystem(bool $isSystem): self
    {
        $this->isSystem = $isSystem;

        return $this;
    }
}
