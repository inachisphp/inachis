<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class UserTotp
{
    /** @var UuidInterface|null The unique identifier for the {@link UserTotp} */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\OneToOne(
        targetEntity: User::class,
        inversedBy: 'totp'
    )]
    #[ORM\JoinColumn(
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private User $user;

    private ?string $secret = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $encryptedSecret = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $encryptedKey = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $enabledAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * UserTotp constructor.
     * Automatically sets the createdAt timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getEncryptedSecret(): ?string
    {
        return $this->encryptedSecret;
    }

    public function setEncryptedSecret(?string $encryptedSecret): self
    {
        $this->encryptedSecret = $encryptedSecret;
        return $this;
    }

    public function getEncryptedKey(): ?string
    {
        return $this->encryptedKey;
    }

    public function setEncryptedKey(?string $encryptedKey): self
    {
        $this->encryptedKey = $encryptedKey;

        return $this;
    }

    public function getEnabledAt(): ?\DateTimeImmutable
    {
        return $this->enabledAt;
    }

    public function setEnabledAt(?\DateTimeImmutable $enabledAt): self
    {
        $this->enabledAt = $enabledAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function hasSecret(): bool
    {
        return $this->encryptedSecret !== null
            && $this->encryptedKey !== null;
    }
}
