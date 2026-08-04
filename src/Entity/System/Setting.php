<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\System;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: 'Inachis\Repository\System\SettingRepository', readOnly: false)]
#[ORM\Index(columns: ['name'], name: 'search_idx')]
class Setting
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private UuidInterface $id;

    /**
     * @var string The setting name, e.g. "site_name", "admin_email", etc.
     */
    #[ORM\Column(type: 'string', length: 191, unique: true)]
    private string $name;

    /** @var string|null The encrypted value of the setting */
    #[ORM\Column(type: 'text')]
    private ?string $encryptedValue = null;

    /** @var int The version of the encryption key used for this setting */
    #[ORM\Column(type: 'smallint')]
    private int $keyVersion = 1;

    /** @var string|null The encrypted key for the setting */
    #[ORM\Column(type: 'text')]
    private ?string $encryptedKey = null;

    /** @var string|null The decrypted value of the setting */
    private ?string $value = null;

    /** @var \DateTimeImmutable The timestamp when the setting was last updated */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?UuidInterface
    {
        return isset($this->id) ? $this->id : null;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the setting name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the setting name.
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the decrypted value of the setting.
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the decrypted value of the setting.
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Get the encrypted value of the setting.
     */
    public function getEncryptedValue(): ?string
    {
        return $this->encryptedValue;
    }

    /**
     * Set the encrypted value of the setting.
     */
    public function setEncryptedValue(?string $encryptedValue): self
    {
        $this->encryptedValue = $encryptedValue;

        return $this;
    }

    /**
     * Get the encryption key version for the setting.
     */
    public function getKeyVersion(): int
    {
        return $this->keyVersion;
    }

    /**
     * Set the encryption key version for the setting.
     */
    public function setKeyVersion(int $keyVersion): self
    {
        $this->keyVersion = $keyVersion;

        return $this;
    }

    /**
     * Get the encrypted key for the setting.
     */
    public function getEncryptedKey(): ?string
    {
        return $this->encryptedKey;
    }

    /**
     * Set the encrypted key for the setting.
     */
    public function setEncryptedKey(?string $encryptedKey): self
    {
        $this->encryptedKey = $encryptedKey;

        return $this;
    }

    /**
     * Get the last updated date for the setting.
     */
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the last updated date.
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
