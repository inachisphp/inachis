<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LoginActivityRepository")
 */
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
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true, nullable=false)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     *
     * @var \Ramsey\Uuid\UuidInterface
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=512, nullable=false)
     * @Assert\NotBlank()
     * @var string The username being logged in as
     */
    private $username;
    /**
     * @ORM\Column(type="string", length=50)
     * @var string The source IP of the login-in attempt
     */
    private $remoteIp;
    /**
     * @ORM\Column(type="string", length=128)
     * @var string A hash of the user-agent detected by the log-in attempt
     */
    private $userAgent;
    /**
     * @ORM\Column(type="integer")
     * @var integer The number of attempts at sign-in during the given period
     */
    private $attemptCount = 1;
    /**
     * @ORM\Column(type="boolean")
     * @var bool Flag indicating if the request result in a temporary block
     */
    private $blockStatus = self::STATUS_UNBLOCKED;
    /**
     * @ORM\Column(type="datetime")
     * @var string The date and time of the attempt
     */
    private $timestamp;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return LoginActivity
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return LoginActivity
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    /**
     * @param string $remoteIp
     * @return LoginActivity
     */
    public function setRemoteIp(string $remoteIp): self
    {
        $this->remoteIp = $remoteIp;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     * @return LoginActivity
     */
    public function setUserAgent(string $userAgent): self
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
    public function setAttemptCount(int $attemptCount): self
    {
        $this->attemptCount = $attemptCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBlockStatus(): bool
    {
        return $this->blockStatus;
    }

    /**
     * @param bool $blockStatus
     * @return LoginActivity
     */
    public function setBlockStatus(bool $blockStatus): self
    {
        $this->blockStatus = $blockStatus;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     * @return LoginActivity
     */
    public function setTimestamp(string $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
