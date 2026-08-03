<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\User;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class UserRecoveryCode
{
    /**
     * The unique identifier for the recovery code.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    /**
     * The user this recovery code belongs to.
     */
    #[ORM\ManyToOne(
        targetEntity: User::class,
        inversedBy: 'recoveryCodes'
    )]
    #[ORM\JoinColumn(
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    /**
     * This is a password-style hash, not an encrypted value.
     *
     * The original recovery code cannot be recovered.
     */
    #[ORM\Column(type: 'text')]
    private string $codeHash;

    /**
     * When this recovery code was created.
     */
    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /**
     * When this recovery code was used.
     *
     * Null indicates the code is still valid.
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $usedAt = null;

    /**
     * UserRecoveryCode constructor.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Get the identifier.
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Set the identifier.
     */
    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the owning user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the owning user.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the hashed recovery code.
     */
    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    /**
     * Set the hashed recovery code.
     */
    public function setCodeHash(string $codeHash): self
    {
        $this->codeHash = $codeHash;

        return $this;
    }

    /**
     * Get when the recovery code was created.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Set when the recovery code was created.
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get when the recovery code was used.
     */
    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    /**
     * Set when the recovery code was used.
     */
    public function setUsedAt(?DateTimeImmutable $usedAt): self
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    /**
     * Determine whether this recovery code has been used.
     */
    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
