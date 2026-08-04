<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity for storing password reset requests.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\User\PasswordResetRequestRepository')]
#[ORM\Table(name: 'password_reset_requests')]
#[ORM\Index(columns: ['user_id', 'token_hash'], name: 'search_idx')]
class PasswordResetRequest
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link PasswordResetRequest}
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var User The User this token relates to
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User\User')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var string Store HMAC hash of token, not raw token
     */
    #[ORM\Column(type: 'string', length: 128)]
    private string $tokenHash;

    /**
     * @var \DateTimeImmutable DateTime the token was created
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var \DateTimeImmutable Expiry DateTime for the token
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    /**
     * @var bool Has this reset token been used already
     */
    #[ORM\Column(type: 'boolean')]
    private bool $used = false;

    /**
     * Creates a new instance of {@link PasswordResetRequest}.
     *
     * @param User               $user      The user this token relates to
     * @param string             $tokenHash The HMAC hash of the token
     * @param \DateTimeImmutable $expiresAt The expiry date and time for the token
     */
    public function __construct(User $user, string $tokenHash, \DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the value of id.
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Get the value of user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Get the value of tokenHash.
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * Get the value of createdAt.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get the value of expiresAt.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Get the value of used.
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * Mark the token as used.
     */
    public function markUsed(): void
    {
        $this->used = true;
    }
}
