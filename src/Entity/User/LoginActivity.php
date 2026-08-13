<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Enum\Security\LoginResultType;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'Inachis\Repository\User\LoginActivityRepository', readOnly: false)]
class LoginActivity
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id;

    /**
     * @var User|null A link to the user if successful
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * @var LoginResultType The result of the login (success|failure)
     */
    #[ORM\Column(type: 'string', length: 255, enumType: LoginResultType::class)]
    private LoginResultType $type;

    /**
     * @var \DateTimeImmutable|null The date and time of the attempt
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $loggedAt;

    /**
     * @var string|null The source IP of the login-in attempt
     */
    #[ORM\Column(type: 'string', length: 50)]
    private ?string $ipAddress = '';

    /**
     * @var string|null A hash of the user-agent detected by the log-in attempt
     */
    #[ORM\Column(type: 'string', length: 256)]
    private ?string $userAgent = '';

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $sessionHash = null;

    /**
     * @var string|null The username failed login attempt was for
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private ?string $username = '';

    /**
     * @var array<string,array<string>|string>|null Anything else worth noting about the login attempt
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraData = null;

    /**
     * {@link LoginActivity} requires success|failure status as a minimum to
     * record login attempts.
     *
     * @param array<string,array<string>|string>|null $extraData
     */
    public function __construct(
        ?User $user,
        LoginResultType $type,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $sessionId = null,
        ?string $username = null,
        ?array $extraData = null,
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->loggedAt = new \DateTimeImmutable();
        $this->ipAddress = $ip;
        $this->userAgent = $userAgent;
        $this->sessionHash = $sessionId ? hash('sha256', $sessionId) : null;
        $this->username = $username ?? $user?->getUserIdentifier();
        $this->extraData = $extraData;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getType(): LoginResultType
    {
        return $this->type;
    }

    public function getLoggedAt(): ?\DateTimeImmutable
    {
        return $this->loggedAt;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getSessionHash(): ?string
    {
        return $this->sessionHash;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return array<string,array<string>|string>|null
     */
    public function getExtraData(): ?array
    {
        return $this->extraData;
    }

    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function setType(LoginResultType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setLoggedAt(?\DateTimeImmutable $loggedAt): self
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function setSessionHash(?string $sessionHash): self
    {
        $this->sessionHash = $sessionHash;

        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @param array<
     *     string,
     *     array<string>|string
     * >|null $extraData
     */
    public function setExtraData(?array $extraData): self
    {
        $this->extraData = $extraData;

        return $this;
    }
}
