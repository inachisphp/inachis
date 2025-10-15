<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\PasswordResetRequestRepository;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: PasswordResetRequestRepository::class)]
#[ORM\Table(name: "password_reset_requests")]
#[ORM\Index(columns: [ "user_id", "token_hash" ], name: "search_idx")]
class PasswordResetRequest
{
    /**
     * @var UuidInterface The unique identifier for the {@link PasswordResetRequest}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;
    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;
    /**
     * @var string Store HMAC hash of token, not raw token
     */
    #[ORM\Column(type: "string", length: 128)]
    private string $tokenHash;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable")]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: "boolean")]
    private bool $used = false;

    public function __construct($user, string $tokenHash, \DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
        $this->used = false;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }
    public function getUser(): User
    {
        return $this->user;
    }
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }
    public function isUsed(): bool
    {
        return $this->used;
    }
    public function markUsed(): void
    {
        $this->used = true;
    }
}
