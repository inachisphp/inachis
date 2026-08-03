<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Content;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Index(columns: ['thread_id'], name: 'thread_idx')]
#[ORM\Index(columns: ['author_id'], name: 'author_idx')]
#[ORM\HasLifecycleCallbacks]
class ReviewComment
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary')]
	#[ORM\GeneratedValue(strategy: 'CUSTOM')]
	#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: ReviewThread::class, inversedBy: 'comments')]
    private ReviewThread $thread;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $author;

    #[Assert\NotBlank]
    #[Assert\Length(max: 10000)]
    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated;

    public function __construct(
        ReviewThread $thread,
        User $author,
        string $message
    ) {
        $this->thread = $thread;
        $this->author = $author;
        $this->message = $message;
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

    public function getThread(): ReviewThread
    {
        return $this->thread;
    }

    public function setThread(ReviewThread $thread): self
    {
        $this->thread = $thread;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

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
}
