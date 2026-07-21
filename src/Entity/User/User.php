<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\User;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\Security\Role;
use Inachis\Entity\User\UserPreference;
use Inachis\Entity\User\UserRecoveryCode;
use Inachis\Entity\User\UserTotp;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Object for handling User entity.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\User\UserRepository')]
#[ORM\Index(columns: [ 'usernameCanonical', 'emailCanonical' ], name: 'search_idx')]
#[UniqueEntity(fields: ['email'], message: 'This email address is already used.')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @var UuidInterface|null The unique identifier for the {@link User} */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /** @var string Username of the user */
    #[ORM\Column(type: "string", length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9]{3,}$/',
        message: 'Username may only contain letters and digits, and must be 3 characters or more.'
    )]
    protected string $username;

    /** @var string|null Username of the user */
    #[ORM\Column(name: 'usernameCanonical', type: "string", length: 255, unique: true, nullable: false)]
    protected ?string $usernameCanonical;

    /** @var string|null Password for the user */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    protected ?string $password;

    /**
     * @var string|null Plaintext version of password - used for validation only and is not stored
     */
    #[Assert\NotBlank(groups: [ 'Default' ])]
    #[Assert\Length(max: 4096)]
    #[Assert\NotCompromisedPassword]
    #[Assert\PasswordStrength(
        minScore: Assert\PasswordStrength::STRENGTH_WEAK,
    )]
    protected ?string $plainPassword;

    /** @var string|null The email address of the user */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    #[Assert\Email]
    #[Assert\NotBlank]
    protected ?string $email;

    /** @var string|null The canonical email address of the user */
    #[ORM\Column(name: 'emailCanonical', type: "string", length: 255, unique: true, nullable: false)]
    protected ?string $emailCanonical;

    /** @var string The display name for the user */
    #[ORM\Column(type: "string", length: 512)]
    #[Assert\NotBlank]
    protected string $displayName = '';

    /** @var string|null An image to use for the {@link User} */
    #[ORM\Column(name: 'avatar', type: "string", length: 255, nullable: true)]
    protected ?string $avatar = '';

    /** @var bool Flag indicating if the {@link User} can sign in */
    #[ORM\Column(type: "boolean")]
    protected bool $isActive = true;

    /** @var bool Flag indicating if the {@link User} has been "deleted" */
    #[ORM\Column(type: "boolean")]
    protected bool $isRemoved = false;

    /** @var DateTimeImmutable The date the {@link User} was added */
    #[ORM\Column(type: "datetime_immutable")]
    protected DateTimeImmutable $createdAt;

    /** @var DateTimeImmutable The date the {@link User} was last modified */
    #[ORM\Column(type: "datetime_immutable")]
    protected DateTimeImmutable $updatedAt;

    /** @var DateTimeImmutable|null The date the password was last modified */
    #[ORM\Column(type: "datetime_immutable")]
    protected ?DateTimeImmutable $passwordChangedAt = null;

    /** @var DateTimeImmutable|null The date the user last logged in */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    /** @var Collection<int, Role> The admin roles assigned to this user */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $assignedRoles;

    /**
     * @var UserPreference|null Preferences for the current {@link User}
     */
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserPreference $preferences = null;

    /** @var UserTotp|null TOTP settings for the user */
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserTotp::class, cascade: ['persist', 'remove'])]
    private ?UserTotp $totp = null;

    /** @var Collection<int, UserRecoveryCode> Recovery codes used by 2FA */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserRecoveryCode::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $recoveryCodes;

    /** @var Collection<int, UserTrustedDevice> Trusted devices for user's 2FA */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: UserTrustedDevice::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $trustedDevices;

    /**
     * Default constructor for {@link User}. If a password is passed into
     * the constructor it will use {@link setPasswordHash} to store a hashed
     * version of the password instead. This entity should never hold
     * the password in plain-text.
     *
     * @param string $username The username for the {@link User}
     * @param string|null $password The password for the {@link User}
     * @param string|null $email The email for the {@link User}
     * @throws Exception
     */
    public function __construct(string $username = '', ?string $password = '', ?string $email = '')
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $this->setAvatar(null);
        $this->assignedRoles = new ArrayCollection();
        $this->recoveryCodes = new ArrayCollection();
        $this->trustedDevices = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Returns the {@link id} of the {@link User}.
     *
     * @return UuidInterface|null The ID of the user
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Returns the {@link username} of the {@link User}.
     *
     * @return string|null The username of the user
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Returns the {@link password} hash for the {@link User}.
     *
     * @return string|null The password hash for the user
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Returns the {@link email} of the {@link User}.
     *
     * @return string|null The email of the user
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Returns the {@link displayName} for the {@link User}.
     *
     * @return string|null The display name for the user
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Gets the roles assigned to this user
     *
     * @return Collection<int,Role>
     */
    public function getAssignedRoles(): Collection
    {
        return $this->assignedRoles;
    }

    /**
     * Adds a specific Role to the User
     *
     * @param Role $role
     * @return self
     */
    public function addAssignedRole(Role $role): self
    {
        if (!$this->assignedRoles->contains($role)) {
            $this->assignedRoles->add($role);
        }

        return $this;
    }

    /**
     * Removes a specific role from the user
     *
     * @param Role $role
     * @return self
     */
    public function removeAssignedRole(Role $role): self
    {
        $this->assignedRoles->removeElement($role);

        return $this;
    }

    /**
     * Returns the role(s) for the current {@link User}
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->assignedRoles as $role) {
            $slug = strtoupper($role->getIdentifier());
            $roles[] = 'ROLE_' . $slug;
            if ($slug === 'ADMIN' || $slug === 'ADMINISTRATOR') {
                $roles[] = 'ROLE_ADMIN';
            }
        }
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Determines if this user is an administrator.
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        foreach ($this->assignedRoles as $role) {
            if ($role->isAdministrator()) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Returns the {@link avatar} for the {@link User}.
     *
     * @return string|null The avatar for the user
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Returns the {@link isActive} for the {@link User}.
     *
     * @return bool Returns check if the user is active
     */
    public function isEnabled(): bool
    {
        return $this->isActive;
    }

    /**
     * Returns the {@link isRemoved} for the {@link User}.
     *
     * @return bool Returns check if the user has been "deleted"
     */
    public function hasBeenRemoved(): bool
    {
        return $this->isRemoved;
    }

    /**
     * Returns the {@link createdAt} for the {@link User}.
     *
     * @return DateTimeImmutable The creation date for the user
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Returns the {@link updatedAt} for the {@link User}.
     *
     * @return DateTimeImmutable The modification for the user
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Returns the {@link passwordChangedAt} for the {@link User}.
     *
     * @return DateTimeImmutable|null The password last modification date for the user
     */
    public function getPasswordChangedAt(): ?DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }

    /**
     * Returns the {@link lastLoginAt} for the {@link User}.
     *
     * @return DateTimeImmutable|null The last login date for the user
     */
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Gets the preferences for the current {@link User}
     *
     * @return UserPreference|null
     */
    public function getPreferences(): ?UserPreference
    {
        return $this->preferences;
    }

    /**
     * Returns the identifier (in this case, username) for the  {@link User}
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * Returns the initials for the {@link User} based on their {@link User.displayName}
     *
     * @return string|null
     */
    public function getInitials(): ?string
    {
        $name = $this->getDisplayName();
        $initials = '';
        if (!empty($name)) {
            $nameWords = explode(' ', $name);
            foreach ($nameWords as $nameWord) {
                $initials .= ucfirst($nameWord[0]);
            }
        }
        return $initials;
    }

    /**
     * Sets the unique id for the {@link User}.
     *
     * @param UuidInterface|null $value The value to set
     * @return self
     */
    public function setId(?UuidInterface $value): self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link username}.
     *
     * @param string $value The value to set
     * @return self
     */
    public function setUsername(string $value): self
    {
        $this->username = $value;
        $this->usernameCanonical = $value;

        return $this;
    }

    /**
     * Sets the value of {@link password}.
     *
     * @param string|null $value The value to set
     * @return self
     */
    public function setPassword(?string $value, ?DateTimeImmutable $now = null): self
    {
        $this->password = $value;
        if ($value !== null) {
            $this->passwordChangedAt = $now ?? new DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @param string|null $value New password to use
     * @return self
     */
    public function setPlainPassword(?string $value): self
    {
        $this->plainPassword = $value;
        $this->password = null;

        return $this;
    }

    /**
     * Sets the value of {@link email}.
     *
     * @param string|null $value The value to set
     * @return self
     */
    public function setEmail(?string $value): self
    {
        $this->email = $value;
        $this->emailCanonical = $value;

        return $this;
    }

    /**
     * Sets the value of {@link displayName}.
     *
     * @param string $value The value to set
     * @return self
     */
    public function setDisplayName(string $value): self
    {
        $this->displayName = $value;

        return $this;
    }

    /**
     * Sets the value of {@link avatar}.
     *
     * @param string|null $value The value to set
     * @return self
     */
    public function setAvatar(?string $value): self
    {
        $this->avatar = $value;

        return $this;
    }

    /**
     * Sets the value of {@link isActive}.
     *
     * @param bool $value The value to set
     * @return self
     */
    public function setActive(bool $value): self
    {
        $this->isActive = $value;

        return $this;
    }

    /**
     * Sets the value of {@link isRemoved}.
     *
     * @param bool $value The value to set
     * @return self
     */
    public function setRemoved(bool $value): self
    {
        $this->isRemoved = $value;

        return $this;
    }

    /**
     * Sets the {@link passwordChangedAt} from a DateTime object.
     *
     * @param DateTimeImmutable $value The date to set
     * @return self
     */
    public function setPasswordChangedAt(DateTimeImmutable $value): self
    {
        $this->passwordChangedAt = $value;

        return $this;
    }

    /**
     * Sets the {@link lastLoginAt} from a DateTime object.
     *
     * @param DateTimeImmutable $value The date to set
     * @return self
     */
    public function setLastLoginAt(DateTimeImmutable $value): self
    {
        $this->lastLoginAt = $value;

        return $this;
    }

    /**
     * Applies preference settings to the {@link User}
     *
     * @param UserPreference $preferences
     * @return self
     */
    public function setPreferences(UserPreference $preferences): self
    {
        $this->preferences = $preferences;
        if ($preferences->getUser() !== $this) {
            $preferences->setUser($this);
        }

        return $this;
    }

    /**
     * Returns the User's {@link UserTotp}
     *
     * @return UserTotp
     */
    public function getTotp(): ?UserTotp
    {
        return $this->totp;
    }

    /**
     * Sets the TOTP for the User
     *
     * @param UserTotp|null $totp
     * @return self
     */
    public function setTotp(?UserTotp $totp): self
    {
        $this->totp = $totp;

        return $this;
    }

    /**
     * Get the recovery codes for the user
     * 
     * @return Collection<int, UserRecoveryCode>
     */
    public function getRecoveryCodes(): Collection
    {
        return $this->recoveryCodes;
    }

    /**
     * Add a recovery code
     *
     * @param UserRecoveryCode $recoveryCode
     * @return self
     */
    public function addRecoveryCode(UserRecoveryCode $recoveryCode): self
    {
        if (!$this->recoveryCodes->contains($recoveryCode)) {
            $this->recoveryCodes->add($recoveryCode);
            $recoveryCode->setUser($this);
        }

        return $this;
    }

    /**
     * Remove a recovery code
     *
     * @param UserRecoveryCode $recoveryCode
     * @return self
     */
    public function removeRecoveryCode(UserRecoveryCode $recoveryCode): self
    {
        if ($this->recoveryCodes->removeElement($recoveryCode)) {
            // Leave the owning side consistent.
            if ($recoveryCode->getUser() === $this) {
                // No setter to null because the relation is non-nullable.
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserTrustedDevice>
     */
    public function getTrustedDevices(): Collection
    {
        return $this->trustedDevices;
    }

    /**
     * Add a trusted device
     *
     * @param UserTrustedDevice $device
     * @return self
     */
    public function addTrustedDevice(
        UserTrustedDevice $device
    ): self {
        if (!$this->trustedDevices->contains($device)) {
            $this->trustedDevices->add($device);
            $device->setUser($this);
        }

        return $this;
    }

    /**
     * Remove a trusted device
     *
     * @param UserTrustedDevice $device
     * @return self
     */
    public function removeTrustedDevice(
        UserTrustedDevice $device
    ): self {
        $this->trustedDevices->removeElement($device);

        return $this;
    }

    /**
     * Checks if TOTP is configured
     *
     * @return boolean
     */
    public function isTotpEnabled(): bool
    {
        return $this->getTotp() !== null &&
            $this->getTotp()->getEnabledAt() !== null;
    }

    /**
     * Removes the credentials for the current {@link User} along
     * with personal information other than "displayName".
     */
    public function erase(): void
    {
        $this->setPassword(null);
        $this->setEmail(null);
        $this->setAvatar(null);
        $this->setActive(false);
        $this->setRemoved(true);
    }

    /**
     * Confirms provided address is generally in the right sort of format
     * to be an email address.
     *
     * @return bool The result of testing the email address
     */
    public function validateEmail(): bool
    {
        return (bool) preg_match(
            '/[a-z0-9!#\$%&\'*+\/=?^_`{|}~-]+' .
            '(?:\.[a-z0-9!#\$%&\'*+\/=?^_`{|}~-]+)' .
            '*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+' .
            '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/',
            $this->email ?? ''
        );
    }

    /**
     * Removes the password for this {@link User}
     *
     * @return void
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}
