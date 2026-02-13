<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity;

use DateTimeImmutable;
use Exception;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Object for handling User entity.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\UserRepository')]
#[ORM\Index(columns: [ 'usernameCanonical', 'emailCanonical' ], name: 'search_idx')]
#[UniqueEntity(fields: ['email'], message: 'This email address is already used.')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Constant for specifying passwords have no expiry time.
     */
    public const NO_PASSWORD_EXPIRY = -1;

    /**
     * @var UuidInterface|null The unique identifier for the {@link User}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    /**
     * @var string|null Username of the user
     */
    #[ORM\Column(type: "string", length: 255, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9]{3,}$/',
        message: 'Username may only contain letters and digits, and must be 3 characters or more.'
    )]
    protected ?string $username;

    /**
     * @var string|null Username of the user
     */
    #[ORM\Column(name: 'usernameCanonical', type: "string", length: 255, unique: true, nullable: false)]
    protected ?string $usernameCanonical;

    /**
     * @var string|null Password for the user
     */
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

    /**
     * @var string|null Email address of the user
     */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    #[Assert\Email]
    #[Assert\NotBlank]
    protected ?string $email;

    /**
     * @var string|null Email address of the user
     */
    #[ORM\Column(name: 'emailCanonical', type: "string", length: 255, unique: true, nullable: false)]
    protected ?string $emailCanonical;

    /**
     * @var string The display name for the user
     */
    #[ORM\Column(type: "string", length: 512)]
    #[Assert\NotBlank]
    protected string $displayName = '';

    /**
     * @var array The roles assigned to this user. Currently, not in use.
     */
    protected array $roles = [];

    /**
     * @var string|null string An image to use for the {@link User}
     */
    #[ORM\Column(name: 'avatar', type: "string", length: 255, nullable: true)]
    protected ?string $avatar = '';

    /**
     * @var bool Flag indicating if the {@link User} can sign in
     */
    #[ORM\Column(type: "boolean")]
    protected bool $isActive = true;

    /**
     * @var bool Flag indicating if the {@link User} has been "deleted"
     */
    #[ORM\Column(type: "boolean")]
    protected bool $isRemoved = false;

    /**
     * @var DateTimeImmutable The date the {@link User} was added
     */
    #[ORM\Column(type: "datetime_immutable")]
    protected DateTimeImmutable $createDate;

    /**
     * @var DateTimeImmutable The date the {@link User} was last modified
     */
    #[ORM\Column(type: "datetime_immutable")]
    protected DateTimeImmutable $modDate;

    /**
     * @var DateTimeImmutable|null The date the password was last modified
     */
    #[ORM\Column(type: "datetime_immutable")]
    protected ?DateTimeImmutable $passwordModDate = null;

    /**
     * @var UserPreference|null Preferences for the current {@link User}
     */
    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserPreference $preferences = null;

    /**
     * Default constructor for {@link User}. If a password is passed into
     * the constructor it will use {@link setPasswordHash} to store a hashed
     * version of the password instead. This entity should never hold
     * the password in plain-text.
     *
     * @param string|null $username The username for the {@link User}
     * @param string|null $password The password for the {@link User}
     * @param string|null $email The email for the {@link User}
     * @throws Exception
     */
    public function __construct(?string $username = '', ?string $password = '', ?string $email = '')
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $this->setAvatar(null);
        $currentTime = new DateTimeImmutable();
        $this->setCreateDate($currentTime);
        $this->setModDate($currentTime);
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
     * Returns the role(s) for the current {@link User}
     * @return array
     */
    public function getRoles(): array
    {
//        $roles = $this->roles;
        $roles = [ 'ROLE_ADMIN' ];
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
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
     * Returns the {@link createDate} for the {@link User}.
     *
     * @return DateTimeImmutable The creation date for the user
     */
    public function getCreateDate(): DateTimeImmutable
    {
        return $this->createDate;
    }

    /**
     * Returns the {@link modDate} for the {@link User}.
     *
     * @return DateTimeImmutable The modification for the user
     */
    public function getModDate(): DateTimeImmutable
    {
        return $this->modDate;
    }

    /**
     * Returns the {@link passwordModDate} for the {@link User}.
     *
     * @return DateTimeImmutable The password last modification date for the user
     */
    public function getPasswordModDate(): DateTimeImmutable
    {
        return $this->passwordModDate;
    }

    /**
     * Gets the preferences for the current {@link User}
     *
     * @return UserPreference|null
     */
    public function getPreferences(): ?UserPreference
    {
        return $this->preferences ?? new UserPreference($this);
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
     * @return string
     */
    public function getInitials(): string
    {
        $initials = '';
        $nameWords = explode(' ', $this->getDisplayName());
        foreach ($nameWords as $nameWord) {
            $initials .= ucfirst($nameWord[0]);
        }
        return $initials;
    }

    /**
     * Sets the value of {@link Id}.
     *
     * @param UuidInterface|null $value The value to set
     * @return $this
     */
    public function setId(?UuidInterface $value): self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link username}.
     *
     * @param string|null $value The value to set
     * @return $this
     */
    public function setUsername(?string $value): self
    {
        $this->username = $value;
        $this->usernameCanonical = $value;

        return $this;
    }

    /**
     * Sets the value of {@link password}.
     *
     * @param string|null $value The value to set
     * @return $this
     */
    public function setPassword(?string $value, ?DateTimeImmutable $now = null): self
    {
        $this->password = $value;
        if ($value !== null) {
            $this->passwordModDate = $now ?? new DateTimeImmutable();
        }
        return $this;
    }

    /**
     * @param string|null $value New password to use
     * @return $this
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
     * @return $this
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
     * @param string|null $value The value to set
     * @return $this
     */
    public function setDisplayName(?string $value): self
    {
        $this->displayName = $value ?? '';

        return $this;
    }

    /**
     * Sets the role(s) for the current {@link User}
     *
     * @param array $roles
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Sets the value of {@link avatar}.
     *
     * @param string|null $value The value to set
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setRemoved(bool $value): self
    {
        $this->isRemoved = $value;

        return $this;
    }

    /**
     * Sets the {@link createDate} from a DateTime object.
     *
     * @param DateTimeImmutable $value The date to be set
     * @return $this
     */
    public function setCreateDate(DateTimeImmutable $value): self
    {
        $this->createDate = $value;

        return $this;
    }

    /**
     * Sets the {@link modDate} from a DateTime object.
     *
     * @param DateTimeImmutable $value The date to set
     * @return $this
     */
    public function setModDate(DateTimeImmutable $value): self
    {
        $this->modDate = $value;

        return $this;
    }

    /**
     * Sets the {@link passwordModDate} from a DateTime object.
     *
     * @param DateTimeImmutable $value The date to set
     * @return $this
     */
    public function setPasswordModDate(DateTimeImmutable $value): self
    {
        $this->passwordModDate = $value;

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
     * Removes the credentials for the current {@link User} along
     * with personal information other than "displayName".
     */
    public function erase(): void
    {
        $this->setUsername(null);
        $this->setPassword(null);
        $this->setEmail(null);
        $this->setAvatar(null);
        $this->setActive(false);
        $this->setRemoved(true);
    }

    /**
     * Determines if the password has expired by adding {@link expiryDays}
     * to the {@link passwordMoDate} and comparing it to the current time.
     * This function can also be used with a notification period to determine
     * if the user should be alerted.
     *
     * @param int $expiryDays The number of days the password expires after
     * @return bool The result of testing the {@link passwordModDate}
     */
    public function hasCredentialsExpired(int $expiryDays = self::NO_PASSWORD_EXPIRY): bool
    {
        if ($expiryDays === self::NO_PASSWORD_EXPIRY || $this->passwordModDate === null) {
            return false;
        }
        return $this->passwordModDate->modify("+{$expiryDays} days") <= new DateTimeImmutable();
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
            $this->email
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
