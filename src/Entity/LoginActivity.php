<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\LoginActivityRepository', readOnly: false)]
class LoginActivity
{
    /**
     *
     */
    const STATUS_UNBLOCKED = false;
    /**
     *
     */
    const STATUS_BLOCKED = true;

    /**
     * @var UuidInterface|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id;

    /**
     * @var string|null The username being logged in as
     */
    #[ORM\Column(type: 'string', length: 512, nullable: false)]
    #[Assert\NotBlank]
    private ?string $username = '';

    /**
     * @var string|null The source IP of the login-in attempt
     */
    #[ORM\Column(type: 'string', length: 50)]
    private ?string $remoteIp = '';

    /**
     * @var string|null A hash of the user-agent detected by the log-in attempt
     */
    #[ORM\Column(type: 'string', length: 256)]
    private ?string $userAgent = '';

    /**
     * @var integer The number of attempts at sign-in during the given period
     */
    #[ORM\Column(type: 'integer')]
    private int $attemptCount = 1;

    /**
     * @var DateTime|null The date and time of the attempt
     */
    #[ORM\Column(type: 'datetime')]
    private ?DateTime $timestamp;

    public function __construct()
    {
        $this->timestamp = new DateTime('now');
    }

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @param UuidInterface|null $id
     * @return LoginActivity
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     * @return LoginActivity
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    /**
     * @param string|null $remoteIp
     * @return LoginActivity
     */
    public function setRemoteIp(?string $remoteIp): self
    {
        $this->remoteIp = $remoteIp;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param string|null $userAgent
     * @return LoginActivity
     */
    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @return int
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * @param int $attemptCount
     * @return LoginActivity
     */
    public function setAttemptCount(int $attemptCount = 0): self
    {
        $this->attemptCount = $attemptCount;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param DateTime|null $timestamp
     * @return LoginActivity
     */
    public function setTimestamp(?DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
