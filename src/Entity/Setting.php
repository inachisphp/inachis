<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: 'Inachis\Repository\SettingRepository', readOnly: false)]
#[ORM\Index(columns: ['name'], name: 'search_idx')]
class Setting
{
    /**
     * @var UuidInterface
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
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

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @param UuidInterface|null $id
     * @return Setting
     */
    public function setId(?UuidInterface $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get the setting name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the setting name
     *
     * @param string $name
     * @return Setting
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the decrypted value of the setting
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the decrypted value of the setting
     *
     * @param string|null $value
     * @return self
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the encrypted value of the setting
     *
     * @return string|null
     */
    public function getEncryptedValue(): ?string
    {
        return $this->encryptedValue;
    }

    /**
     * Set the encrypted value of the setting
     *
     * @param string|null $encryptedValue
     * @return self
     */
    public function setEncryptedValue(?string $encryptedValue): self
    {
        $this->encryptedValue = $encryptedValue;

        return $this;
    }

    /**
     * Get the encryption key version for the setting
     *
     * @return integer
     */
    public function getKeyVersion(): int
    {
        return $this->keyVersion;
    }

    /**
     * Set the encryption key version for the setting
     *
     * @param integer $keyVersion
     * @return self
     */
    public function setKeyVersion(int $keyVersion): self
    {
        $this->keyVersion = $keyVersion;

        return $this;
    }

    /**
     * Get the encrypted key for the setting
     *
     * @return string|null
     */
    public function getEncryptedKey(): ?string
    {
        return $this->encryptedKey;
    }

    /**
     * Set the encrypted key for the setting
     *
     * @param string|null $encryptedKey
     * @return self
     */
    public function setEncryptedKey(?string $encryptedKey): self
    {
        $this->encryptedKey = $encryptedKey;

        return $this;
    }
}
