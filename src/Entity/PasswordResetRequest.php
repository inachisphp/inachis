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
use Inachis\Repository\PasswordResetRequestRepository;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: PasswordResetRequestRepository::class)]
#[ORM\Table(name: "password_reset_requests")]
#[ORM\Index(columns: [ "user_id", "token_hash" ], name: "search_idx")]
class PasswordResetRequest
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link PasswordResetRequest}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var User The User this token relates to
     */
    #[ORM\ManyToOne(targetEntity: 'Inachis\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var string Store HMAC hash of token, not raw token
     */
    #[ORM\Column(type: "string", length: 128)]
    private string $tokenHash;

    /**
     * @var DateTimeImmutable DateTime the token was created
     */
    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable Expiry DateTime for the token
     */
    #[ORM\Column(type: "datetime_immutable")]
    private DateTimeImmutable $expiresAt;

    /**
     * @var bool Has this reset token been used already
     */
    #[ORM\Column(type: "boolean")]
    private bool $used = false;

    /**
     * @param $user
     * @param string            $tokenHash
     * @param DateTimeImmutable $expiresAt
     */
    public function __construct($user, string $tokenHash, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->createdAt = new DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return boolean
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * @return void
     */
    public function markUsed(): void
    {
        $this->used = true;
    }
}
