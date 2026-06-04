<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Content;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class ReviewThread
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
	#[ORM\GeneratedValue(strategy: 'CUSTOM')]
	#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
	private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Page::class)]
    private Page $page;

    #[ORM\OneToMany(
        mappedBy: 'thread',
        targetEntity: ReviewComment::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['created' => 'ASC'])]
    protected Collection $comments;

    #[ORM\Column(type: 'string', length: 20)]
    protected string $status = 'open';

    #[ORM\Column(type: 'boolean')]
    protected bool $needsRebase = false;

    #[ORM\ManyToOne(targetEntity: Revision::class)]
    protected ?Revision $createdRevision = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    protected User $createdBy;

    #[ORM\Column(type: 'integer')]
    protected int $startOffset;

    #[ORM\Column(type: 'integer')]
    protected int $endOffset;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $currentStartOffset = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $currentEndOffset = null;

    #[ORM\Column(type: 'text')]
    protected string $selectedText;

    #[ORM\Column(type: 'text')]
    protected string $contextBefore;

    #[ORM\Column(type: 'text')]
    protected string $contextAfter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $assignedTo = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $resolved = false;

    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $updated;

    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $resolvedBy = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->created = new DateTimeImmutable();
        $this->updated = new DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    public function setPage(Page $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isNeedsRebase(): bool
    {
        return $this->needsRebase;
    }

    public function setNeedsRebase(bool $needsRebase): self
    {
        $this->needsRebase = $needsRebase;

        return $this;
    }

    public function getCreatedRevision(): ?Revision
    {
        return $this->createdRevision;
    }

    public function setCreatedRevision(?Revision $createdRevision): self
    {
        $this->createdRevision = $createdRevision;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getStartOffset(): int
    {
        return $this->startOffset;
    }

    public function setStartOffset(int $startOffset): self
    {
        $this->startOffset = $startOffset;

        return $this;
    }

    public function getEndOffset(): int
    {
        return $this->endOffset;
    }

    public function setEndOffset(int $endOffset): self
    {
        $this->endOffset = $endOffset;

        return $this;
    }

    public function getCurrentStartOffset(): ?int
    {
        return $this->currentStartOffset;
    }

    public function setCurrentStartOffset(?int $currentStartOffset): self
    {
        $this->currentStartOffset = $currentStartOffset;

        return $this;
    }

    public function getCurrentEndOffset(): ?int
    {
        return $this->currentEndOffset;
    }

    public function setCurrentEndOffset(?int $currentEndOffset): self
    {
        $this->currentEndOffset = $currentEndOffset;

        return $this;
    }

    public function getSelectedText(): string
    {
        return $this->selectedText;
    }

    public function setSelectedText(string $selectedText): self
    {
        $this->selectedText = $selectedText;

        return $this;
    }

    public function getContextBefore(): string
    {
        return $this->contextBefore;
    }

    public function setContextBefore(string $contextBefore): self
    {
        $this->contextBefore = $contextBefore;

        return $this;
    }

    public function getContextAfter(): string
    {
        return $this->contextAfter;
    }

    public function setContextAfter(string $contextAfter): self
    {
        $this->contextAfter = $contextAfter;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): self
    {
        $this->resolved = $resolved;

        return $this;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(DateTimeImmutable $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeImmutable $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getResolvedBy(): ?User
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?User $resolvedBy): self
    {
        $this->resolvedBy = $resolvedBy;

        return $this;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}
