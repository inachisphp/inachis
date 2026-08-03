<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Content;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Inachis\Enum\ReviewStatus;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ReviewThread
{
    /** @var UuidInterface The unqiue identifier for the review thread */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary')]
	#[ORM\GeneratedValue(strategy: 'CUSTOM')]
	#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
	private ?UuidInterface $id = null;

    /** @var Page The page the review thread is for */
    #[ORM\ManyToOne(targetEntity: Page::class)]
    private Page $page;

    /** @var Collection<int, ReviewComment> Comments associated with this review thread */
    #[ORM\OneToMany(
        mappedBy: 'thread',
        targetEntity: ReviewComment::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['created' => 'ASC'])]
    protected Collection $comments;

    /** @var ReviewStatus The current status of this thread */
    #[ORM\Column(enumType: ReviewStatus::class)]
    protected ReviewStatus $status = ReviewStatus::OPEN;

    /** @var bool Flag indicating if the offsets need rebasing after content change */
    #[ORM\Column(type: 'boolean')]
    protected bool $needsRebase = false;

    /** @var User The user who started this review thread */
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected User $createdBy;

    /** @var int The starting offset in the content for the review thread */
    #[ORM\Column(type: 'integer')]
    protected int $startOffset;

    /** @var int The ending offset in the content for the review thread */
    #[ORM\Column(type: 'integer')]
    protected int $endOffset;

    /** @var int The current starting offset in the content for the review thread */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $currentStartOffset = null;

    /** @var int The current ending offset in the content for the review thread */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $currentEndOffset = null;

    /** @var string The text content of the selection */
    #[ORM\Column(type: 'text')]
    protected string $selectedText;

    /** @var string The content before the selected text */
    #[ORM\Column(type: 'text')]
    protected string $contextBefore;

    /** @var string The content after he selected text */
    #[ORM\Column(type: 'text')]
    protected string $contextAfter;

    /** @var User The user this review has been assigned to */
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $assignedTo = null;

    /** @var DateTimeImmutable The datetime this review was started  */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $created;

    /** @var DateTimeImmutable The datetime this review was last updated  */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $updated;

    /** @var User The user who resolved this review thread  */
    #[ORM\ManyToOne(targetEntity: User::class)]
    protected ?User $resolvedBy = null;

    /** @var DateTimeImmutable The datetime this review thread was resolved  */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected ?DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $now = new DateTimeImmutable();

        $this->created = $now;
        $this->updated = $now;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
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

    /**
     * Returns the collection of comments for this review
     *
     * @return Collection<int, ReviewComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(ReviewComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setThread($this);
        }

        return $this;
    }

    public function removeComment(ReviewComment $comment): self
    {
        $this->comments->removeElement($comment);

        return $this;
    }

    public function getStatus(): ReviewStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewStatus $status): self
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
        if ($endOffset < $this->startOffset) {
            throw new \InvalidArgumentException(
                'End offset cannot be before start offset'
            );
        }

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
        return $this->status === ReviewStatus::RESOLVED;
    }

    public function resolve(User $user): self
    {
        $this->status = ReviewStatus::RESOLVED;
        $this->resolvedBy = $user;
        $this->resolvedAt = new DateTimeImmutable();

        return $this;
    }

    public function reopen(): self
    {
        $this->status = ReviewStatus::OPEN;
        $this->resolvedBy = null;
        $this->resolvedAt = null;

        return $this;
    }

    public function close(): self
    {
        $this->status = ReviewStatus::CLOSED;

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
