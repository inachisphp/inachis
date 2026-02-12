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
use Inachis\Entity\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'Inachis\Repository\LoginActivityRepository', readOnly: false)]
class LoginActivity
{
    /**
     * @var UuidInterface|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
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
     * @var string The result of the login (success|failure)
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    /**
     * @var DateTimeImmutable|null The date and time of the attempt
     */
    #[ORM\Column]
    private ?DateTimeImmutable $loggedAt;

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

    /**
     * @var string|null
     */
     #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $sessionHash = null;

    /**
     * @var string|null The username failed login attempt was for
     */
    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    #[Assert\NotBlank]
    private ?string $username = '';

    /**
     * @var array|null Anything else worth noting about the login attempt
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extraData = null;

    /**
     * {@link LoginActivity} requires success|failure status as a minimum to
     * record login attempts.
     *
     * @param User|null $user
     * @param string $type
     * @param string|null $ip
     * @param string|null $userAgent
     * @param string|null $sessionId
     * @param string|null $username
     * @param array|null $extraData
     */
    public function __construct(
        ?User $user,
        string $type,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $sessionId = null,
        ?string $username = null,
        ?array $extraData = null
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->loggedAt = new DateTimeImmutable();
        $this->ipAddress = $ip;
        $this->userAgent = $userAgent;
        $this->sessionHash = $sessionId ? hash('sha256', $sessionId) : null;
        $this->username = $username ?? $user?->getUserIdentifier();
        $this->extraData = $extraData;
    }

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLoggedAt(): ?DateTimeImmutable
    {
        return $this->loggedAt;
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @return string|null
     */
    public function getSessionHash(): ?string
    {
        return $this->sessionHash;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return array|null
     */
    public function getExtraData(): ?array
    {
        return $this->extraData;
    }

    /**
     * @param UuidInterface|null $id
     * @return self
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param User|null $user
     * @return self
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param DateTimeImmutable|null $loggedAt
     * @return self
     */
    public function setLoggedAt(?DateTimeImmutable $loggedAt): self
    {
        $this->loggedAt = $loggedAt;
        return $this;
    }

    /**
     * @param string|null $ipAddress
     * @return self
     */
    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * @param string|null $userAgent
     * @return self
     */
    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @param string|null $sessionHash
     * @return self
     */
    public function setSessionHash(?string $sessionHash): self
    {
        $this->sessionHash = $sessionHash;
        return $this;
    }

    /**
     * @param string|null $username
     * @return self
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param array|null $extraData
     * @return self
     */
    public function setExtraData(?array $extraData): self
    {
        $this->extraData = $extraData;
        return $this;
    }
}
