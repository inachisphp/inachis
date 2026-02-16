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
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity for handling security policy.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class SecurityPolicy
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
     * @var string The name of the security policy
     */
    #[ORM\Column]
    private string $name = '';

    /**
     * @var int The minimum length for a password
     */
    #[ORM\Column]
    private int $minLength = 12;

    /**
     * @var bool Flag indicating if the password must contain an uppercase letter
     */
    #[ORM\Column]
    private bool $requireUppercase = true;

    /**
     * @var bool Flag indicating if the password must contain a lowercase letter
     */
    #[ORM\Column]
    private bool $requireLowercase = true;

    /**
     * @var bool Flag indicating if the password must contain a number
     */
    #[ORM\Column]
    private bool $requireNumber = true;

    /**
     * @var bool Flag indicating if the password must contain a special character
     */
    #[ORM\Column]
    private bool $requireSpecial = true;

    /**
     * @var string|null Custom regex pattern for password validation
     */
    #[ORM\Column(nullable: true)]
    private ?string $passwordRegex = null;

    /**
     * @var int|null Password expiration in days, null if never expires
     */
    #[ORM\Column(nullable: true)]
    private ?int $passwordExpiryDays = null;

    /**
     * @var int The number of previous passwords to remember
     */
    #[ORM\Column]
    private int $passwordHistory = 5;

    /**
     * @var int Number of failed login attempts before locking the account
     */
    #[ORM\Column]
    private int $maxFailedLoginAttempts = 5;

    /**
     * @var int Lockout duration in minutes
     */
    #[ORM\Column]
    private int $lockoutDurationMinutes = 15;

    /**
     * @var bool Flag indicating if administrators must use 2FA
     */
    #[ORM\Column(name: 'admin_require_2fa')]
    private bool $adminRequire2FA = false;

    /**
     * @var bool Flag indicating if super administrators must use 2FA
     */
    #[ORM\Column(name: 'super_admin_require_2fa')]
    private bool $superAdminRequire2FA = false;

    /**
     * @var bool Flag indicating if super administrators must use WebAuthn
     */
    #[ORM\Column(name: 'super_admin_requires_webauthn')]
    private bool $superAdminRequiresWebAuthn = false;

    /**
     * @var bool Flag indicating if step up is required for sensitive actions
     */
    #[ORM\Column]
    private bool $stepUpForSensitiveActions = true;

    /**
     * @var DateTimeImmutable The date and time the security policy was created
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var DateTimeImmutable The date and time the security policy was updated
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * @var bool Flag indicating if the security policy is read-only
     */
    #[ORM\Column]
    private bool $isReadOnly = false;

    /**
     * @var bool Flag indicating if the security policy is active
     */
    #[ORM\Column]
    private bool $isActive = false;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Set created and updated time on create
     */
    #[ORM\PrePersist]
    private function onPrePersist(): void {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Set updated time on update
     */
    #[ORM\PreUpdate]
    private function onPreUpdate(): void {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Get the unique identifier for the {@link SecurityPolicy}
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // /**
    //  * Get flag indicating if the password policy should be enforced
    //  * @return bool
    //  */
    // public function getEnforcePasswordPolicy(): bool
    // {
    //     return $this->enforcePasswordPolicy;
    // }

    // /**
    //  * Set flag indicating if the password policy should be enforced
    //  * @param bool $enforcePasswordPolicy
    //  * @return self
    //  */
    // public function setEnforcePasswordPolicy(bool $enforcePasswordPolicy): self
    // {
    //     $this->enforcePasswordPolicy = $enforcePasswordPolicy;
    //     return $this;
    // }

    /**
     * Get the minimum length for a password
     * @return int
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * Set the minimum length for a password
     * @param int $minLength
     * @return self
     */
    public function setMinLength(int $minLength): self
    {
        if ($minLength < 1) {
            throw new InvalidArgumentException('Password minimum length must be at least 1.');
        }
        $this->minLength = $minLength;
        return $this;
    }

    /**
     * Get flag indicating if the password must contain an uppercase letter
     * @return bool
     */
    public function getRequireUppercase(): bool
    {
        return $this->requireUppercase;
    }

    /**
     * Set flag indicating if the password must contain an uppercase letter
     * @param bool $requireUppercase
     * @return self
     */
    public function setRequireUppercase(bool $requireUppercase): self
    {
        $this->requireUppercase = $requireUppercase;
        return $this;
    }

    /**
     * Get flag indicating if the password must contain a lowercase letter
     * @return bool
     */
    public function getRequireLowercase(): bool
    {
        return $this->requireLowercase;
    }

    /**
     * Set flag indicating if the password must contain a lowercase letter
     * @param bool $requireLowercase
     * @return self
     */
    public function setRequireLowercase(bool $requireLowercase): self
    {
        $this->requireLowercase = $requireLowercase;
        return $this;
    }

    /**
     * Get flag indicating if the password must contain a number
     * @return bool
     */
    public function getRequireNumber(): bool
    {
        return $this->requireNumber;
    }

    /**
     * Set flag indicating if the password must contain a number
     * @param bool $requireNumber
     * @return self
     */
    public function setRequireNumber(bool $requireNumber): self
    {
        $this->requireNumber = $requireNumber;
        return $this;
    }

    /**
     * Get flag indicating if the password must contain a special character
     * @return bool
     */
    public function getRequireSpecial(): bool
    {
        return $this->requireSpecial;
    }

    /**
     * Set flag indicating if the password must contain a special character
     * @param bool $requireSpecial
     * @return self
     */
    public function setRequireSpecial(bool $requireSpecial): self
    {
        $this->requireSpecial = $requireSpecial;
        return $this;
    }

    /**
     * Get custom regex pattern for password validation
     * @return string|null
     */
    public function getPasswordRegex(): ?string
    {
        return $this->passwordRegex;
    }

    /**
     * Set custom regex pattern for password validation
     * @param string|null $passwordRegex
     * @return self
     */
    public function setPasswordRegex(?string $passwordRegex): self
    {
        if ($passwordRegex !== null) {
            if (@preg_match($passwordRegex, '') === false) {
                throw new InvalidArgumentException('Invalid regex pattern provided.');
            }
        }
        $this->passwordRegex = $passwordRegex;
        return $this;
    }

    /**
     * Get password expiration in days, null if never expires
     * @return int|null
     */
    public function getPasswordExpiryDays(): ?int
    {
        return $this->passwordExpiryDays;
    }

    /**
     * Set password expiration in days, null if never expires
     * @param int|null $passwordExpiryDays
     * @return self
     */
    public function setPasswordExpiryDays(?int $passwordExpiryDays): self
    {
        if ($passwordExpiryDays !== null && $passwordExpiryDays < 1) {
            throw new InvalidArgumentException('Password expiry must be positive or null.');
        }
        $this->passwordExpiryDays = $passwordExpiryDays;
        return $this;
    }

    /**
     * Get the number of previous passwords to remember
     * @return int
     */
    public function getPasswordHistory(): int
    {
        return $this->passwordHistory;
    }

    /**
     * Set the number of previous passwords to remember
     * @param int $passwordHistory
     * @return self
     */
    public function setPasswordHistory(int $passwordHistory): self
    {
        if ($passwordHistory < 0) {
            throw new InvalidArgumentException('Password history must be zero or positive.');
        }
        $this->passwordHistory = $passwordHistory;
        return $this;
    }

    /**
     * Get number of failed login attempts before locking the account
     * @return int
     */
    public function getMaxFailedLoginAttempts(): int
    {
        return $this->maxFailedLoginAttempts;
    }

    /**
     * Set number of failed login attempts before locking the account
     * @param int $maxFailedLoginAttempts
     * @return self
     */
    public function setMaxFailedLoginAttempts(int $maxFailedLoginAttempts): self
    {
        if ($maxFailedLoginAttempts < 1) {
            throw new InvalidArgumentException('Max failed login attempts must be at least 1.');
        }
        $this->maxFailedLoginAttempts = $maxFailedLoginAttempts;
        return $this;
    }

    /**
     * Get lockout duration in minutes
     * @return int
     */
    public function getLockoutDurationMinutes(): int
    {
        return $this->lockoutDurationMinutes;
    }

    /**
     * Set lockout duration in minutes
     * @param int $lockoutDurationMinutes
     * @return self
     */
    public function setLockoutDurationMinutes(int $lockoutDurationMinutes): self
    {
        if ($lockoutDurationMinutes < 1) {
            throw new InvalidArgumentException('Lockout duration must be at least 1 minute.');
        }
        $this->lockoutDurationMinutes = $lockoutDurationMinutes;
        return $this;
    }

    /**
     * Get flag indicating if administrators must use 2FA
     * @return bool
     */
    public function getAdminRequire2FA(): bool
    {
        return $this->adminRequire2FA;
    }

    /**
     * Set flag indicating if administrators must use 2FA
     * @param bool $adminRequire2FA
     * @return self
     */
    public function setAdminRequire2FA(bool $adminRequire2FA): self
    {
        $this->adminRequire2FA = $adminRequire2FA;
        return $this;
    }

    /**
     * Get flag indicating if super administrators must use 2FA
     * @return bool
     */
    public function getSuperAdminRequire2FA(): bool
    {
        return $this->superAdminRequire2FA;
    }

    /**
     * Set flag indicating if super administrators must use 2FA
     * @param bool $superAdminRequire2FA
     * @return self
     */
    public function setSuperAdminRequire2FA(bool $superAdminRequire2FA): self
    {
        $this->superAdminRequire2FA = $superAdminRequire2FA;
        return $this;
    }

    /**
     * Get flag indicating if super administrators must use WebAuthn
     * @return bool
     */
    public function getSuperAdminRequiresWebAuthn(): bool
    {
        return $this->superAdminRequiresWebAuthn;
    }

    /**
     * Set flag indicating if super administrators must use WebAuthn
     * @param bool $superAdminRequiresWebAuthn
     * @return self
     */
    public function setSuperAdminRequiresWebAuthn(bool $superAdminRequiresWebAuthn): self
    {
        $this->superAdminRequiresWebAuthn = $superAdminRequiresWebAuthn;
        return $this;
    }

    /**
     * Get flag indicating if step up is required for sensitive actions
     * @return bool
     */
    public function getStepUpForSensitiveActions(): bool
    {
        return $this->stepUpForSensitiveActions;
    }

    /**
     * Set flag indicating if step up is required for sensitive actions
     * @param bool $stepUpForSensitiveActions
     * @return self
     */
    public function setStepUpForSensitiveActions(bool $stepUpForSensitiveActions): self
    {
        $this->stepUpForSensitiveActions = $stepUpForSensitiveActions;
        return $this;
    }

    /**
     * Get creation timestamp
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get last update timestamp
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get flag indicating if the security policy is read-only
     * @return bool
     */
    public function getIsReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * Set flag indicating if the security policy is read-only
     * @param bool $isReadOnly
     * @return self
     */
    public function setIsReadOnly(bool $isReadOnly): self
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }

    /**
     * Get flag indicating if the security policy is active
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Set flag indicating if the security policy is active
     * @param bool $isActive
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}
