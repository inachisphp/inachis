<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling {@link Waste} contents
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\WasteRepository', readOnly: false)]
#[ORM\Index(name: 'search_idx', columns: ['source_type', 'user_id'])]
class Waste
{
    /**
     * @var UuidInterface The unique id of the waste item
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private UuidInterface $id;
    /**
     * @var string|null The entity type
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $sourceType = '';
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $sourceName = '';
    /**
     * @var string|null The former title for the item
     */
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = '';
    /**
     * @var string|null The contents of the item deleted
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = '';
    /**
     * @var User The author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;
    /**
     * @var DateTimeImmutable The date the item was added to the bin
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $modDate;

    /**
     * Gets the value of {@link id}.
     * 
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets the value of {@link id}.
     * 
     * @param UuidInterface $id The id to set
     * @return Waste
     */
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Gets the value of {@link sourceType}.
     * 
     * @return string|null
     */
    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    /**
     * Sets the value of {@link sourceType}.
     * 
     * @param string|null $sourceType The source type to set
     * @return Waste
     */
    public function setSourceType(?string $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }


    /**
     * Gets the value of {@link sourceName}.
     * 
     * @return string|null
     */
    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    /**
     * Sets the value of {@link sourceName}.
     * 
     * @param string|null $sourceName The source name to set
     * @return Waste
     */
    public function setSourceName(?string $sourceName): self
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /**
     * Gets the value of {@link title}.
     * 
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the value of {@link title}.
     * 
     * @param string|null $title The title to set
     * @return Waste
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the value of {@link content}.
     * 
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the value of {@link content}.
     * 
     * @param string|null $content The content to set
     * @return Waste
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the value of {@link modDate}.
     * 
     * @return DateTimeImmutable|null The date the content was deleted
     */
    public function getModDate(): ?DateTimeImmutable
    {
        return $this->modDate;
    }

    /**
     * Sets the value of {@link modDate}.
     * 
     * @param DateTimeImmutable $value The modification date to set
     * @return Waste
     */
    public function setModDate(DateTimeImmutable $value): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * Gets the value of {@link user}.
     * 
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the value of {@link user}.
     *
     * @param User $value The UUID of user adding the {@link Waste}
     * @return Waste
     */
    public function setUser(User $value): self
    {
        $this->user = $value;

        return $this;
    }
}
