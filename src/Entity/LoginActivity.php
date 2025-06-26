<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
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
     * @var \Ramsey\Uuid\UuidInterface
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private $id;

    /**
     * @var string The username being logged in as
     */
    #[ORM\Column(type: 'string', length: 512, nullable: false)]
    #[ORM\Assert\NotBlank]
    private $username;

    /**
     * @var string The source IP of the login-in attempt
     */
    #[ORM\Column(type: 'string', length: 50)]
    private $remoteIp;

    /**
     * @var string A hash of the user-agent detected by the log-in attempt
     */
    #[ORM\Column(type: 'string', length: 128)]
    private $userAgent;

    /**
     * @var integer The number of attempts at sign-in during the given period
     */
    #[ORM\Column(type: 'integer')]
    private $attemptCount = 1;

    /**
     * @var bool Flag indicating if the request result in a temporary block
     */
    #[ORM\Column(type: 'boolean')]
    private $blockStatus = self::STATUS_UNBLOCKED;
    /**
     * @var string The date and time of the attempt
     */
    #[ORM\Column(type: 'datetime')]
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setTimestamp(string $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
