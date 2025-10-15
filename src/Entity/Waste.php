<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling {@link Waste} contents
 */
#[ORM\Entity(repositoryClass: 'App\Repository\WasteRepository', readOnly: false)]
#[ORM\Index(columns: ['source_type', 'user_id'], name: 'search_idx')]
class Waste
{
    /**
     * @var UuidInterface|null The unique id of the waste item
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id;
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
     * @var User|null The author for the {@link Page}
     */
    #[ORM\ManyToOne(targetEntity: 'User', cascade: ['detach'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user;
    /**
     * @var DateTime The date the item was added to the bin
     */
    #[ORM\Column(type: 'datetime')]
    protected DateTime $modDate;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param UuidInterface $id
     * @return $this
     */
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    /**
     * @param string|null $sourceType
     * @return $this
     */
    public function setSourceType(?string $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    /**
     * @param string|null $sourceName
     * @return $this
     */
    public function setSourceName(?string $sourceName): self
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return DateTimeInterface|null The date the content was deleted
     */
    public function getModDate(): ?DateTimeInterface
    {
        return $this->modDate;
    }

    /**
     * @param DateTime|null $value
     * @return $this
     */
    public function setModDate(DateTime $value = null): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Sets the value of {@link author}.
     *
     * @param User|null $value The UUID of user adding the {@link Waste}
     * @return $this
     */
    public function setUser(User $value = null): self
    {
        $this->user = $value;

        return $this;
    }
}
