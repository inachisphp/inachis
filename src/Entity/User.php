<?php

namespace App\Entity;

use App\Exception\InvalidTimezoneException;
use App\Validator\DateValidator;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as InachisAssert;

/**
 * Object for handling User entity.
 */
#[ORM\Entity(repositoryClass: "App\Repository\UserRepository", readOnly: false)]
#[ORM\Index(name: "search_idx", columns: [ "usernameCanonical", "emailCanonical" ])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Constant for specifying passwords have no expiry time.
     */
    const NO_PASSWORD_EXPIRY = -1;

    /**
     * @var \Ramsey\Uuid\UuidInterface The unique identifier for the {@link User}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected $id;

    /**
     * @var string Username of the user
     */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    #[Assert\NotBlank]
    protected $username;

    /**
     * @var string Username of the user
     */
    #[ORM\Column(type: "string", length: 255, name: 'usernameCanonical', unique: true, nullable: false)]
    protected $usernameCanonical;

    /**
     * @var string Password for the user
     */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    protected $password;

    /**
     * @var string Plaintext version of password - used for validation only and is not stored
     */
    #[Assert\NotBlank]
    #[Asset\Length(max: 4096)]
    #[Assert\NotCompromisedPassword]
    #[Assert\PasswordStrength]
    protected $plainPassword;

    /**
     * @var string Email address of the user
     */
    #[ORM\Column(type: "string", length: 512, nullable: false)]
    protected $email;

    /**
     * @var string Email address of the user
     */
    #[ORM\Column(type: "string", length: 255, name: 'emailCanonical', unique: true, nullable: false)]
    protected $emailCanonical;

    /**
     * @var string The display name for the user
     */
    #[ORM\Column(type: "string", length: 512)]
    protected $displayName;

    /**
     * @var Image string An image to use for the {@link User}
     */
    protected $avatar;

    /**
     * @var bool Flag indicating if the {@link User} can sign in
     */
    #[ORM\Column(type: "boolean")]
    protected $isActive = true;

    /**
     * @var bool Flag indicating if the {@link User} has been "deleted"
     */
    #[ORM\Column(type: "boolean")]
    protected $isRemoved = false;

    /**
     * @var string The date the {@link User} was added
     */
    #[ORM\Column(type: "datetime")]
    protected $createDate;

    /**
     * @var string The date the {@link User} was last modified
     */
    #[ORM\Column(type: "datetime")]
    protected $modDate;

    /**
     * @var string The date the password was last modified
     */
    #[ORM\Column(type: "datetime")]
    protected $passwordModDate;

    /**
     * @InachisAssert\ValidTimezone()
     * @var string The local timezone for the user
     */
    #[ORM\Column(type: "string",length: 32, options: ["default" => "UTC" ])]
    #[Assert\NotBlank]
    protected $timezone;

    /**
     * Default constructor for {@link User}. If a password if passed into
     * the constructor it will use {@link setPasswordHash} to store a hashed
     * version of the password instead. This entity should never hold
     * the password in plain-text.
     *
     * @param string $username The username for the {@link User}
     * @param string $password The password for the {@link User}
     * @param string $email    The email for the {@link User}
     * @throws \Exception
     */
    public function __construct(string $username = '', string $password = '', string $email = '')
    {
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $currentTime = new \DateTime('now');
        $this->setCreateDate($currentTime);
        $this->setModDate($currentTime);
        $this->setPasswordModDate($currentTime);
        $this->setTimezone('UTC');
    }

    /**
     * Returns the {@link id} of the {@link User}.
     *
     * @return string The ID of the user
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Returns the {@link username} of the {@link User}.
     *
     * @return string The username of the user
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Returns the {@link password} hash for the {@link User}.
     *
     * @return string The password hash for the user
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    /**
     * Returns the {@link email} of the {@link User}.
     *
     * @return string The email of the user
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Returns the {@link displayName} for the {@link User}.
     *
     * @return string The display name for the user
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Returns the {@link avatar} for the {@link User}.
     *
     * @return \App\Entity\Image The avatar for the user
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
     * @return string The creation date for the user
     */
    public function getCreateDate(): \DateTime
    {
        return $this->createDate;
    }

    /**
     * Returns the {@link modDate} for the {@link User}.
     *
     * @return string The modification for the user
     */
    public function getModDate(): \DateTime
    {
        return $this->modDate;
    }

    /**
     * Returns the {@link timezone} for the {@link User}.
     *
     * @return string The local timezone for the user
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * Returns the {@link passwordModDate} for the {@link User}.
     *
     * @return string The password last modification date for the user
     */
    public function getPasswordModDate(): \DateTime
    {
        return $this->passwordModDate;
    }

    /**
     * Sets the value of {@link Id}.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setId(string $value): self
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the value of {@link username}.
     *
     * @param string $value The value to set
     * @return $this
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
     * @param string $value The value to set
     * @return $this
     */
    public function setPassword(string $value): self
    {
        $this->password = $value;

        return $this;
    }

    /**
     * @param string $value New password to use
     * @return $this
     */
    public function setPlainPassword(string $value): self
    {
        $this->plainPassword = $value;
        $this->password = null;

        return $this;
    }

    /**
     * Sets the value of {@link email}.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setEmail(string $value): self
    {
        $this->email = $value;
        $this->emailCanonical = $value;

        return $this;
    }

    /**
     * Sets the value of {@link displayName}.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setDisplayName(string $value): self
    {
        $this->displayName = $value;

        return $this;
    }

    /**
     * Sets the value of {@link avatar}.
     *
     * @param string $value The value to set
     * @return $this
     */
    public function setAvatar($value): self
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
    public function setActive($value): self
    {
        $this->isActive = (bool) $value;

        return $this;
    }

    /**
     * Sets the value of {@link isRemoved}.
     *
     * @param bool $value The value to set
     * @return $this
     */
    public function setRemoved($value): self
    {
        $this->isRemoved = (bool) $value;

        return $this;
    }

    /**
     * Sets the {@link createDate} from a DateTime object.
     *
     * @param \DateTime $value The date to be set
     * @return $this
     */
    public function setCreateDate(\DateTime $value): self
    {
        //$this->setCreateDate($value->format('Y-m-d H:i:s'));
        $this->createDate = $value;

        return $this;
    }

    /**
     * Sets the {@link modDate} from a DateTime object.
     *
     * @param \DateTime $value The date to set
     * @return $this
     */
    public function setModDate(\DateTime $value): self
    {
        //$this->setModDate($value->format('Y-m-d H:i:s'));
        $this->modDate = $value;

        return $this;
    }

    /**
     * Sets the {@link passwordModDate} from a DateTime object.
     *
     * @param \DateTime $value The date to set
     * @return $this
     */
    public function setPasswordModDate(\DateTime $value): self
    {
        $this->passwordModDate = $value;

        return $this;
    }

    /**
     * @param string $value
     * @return $this
     * @throws InvalidTimezoneException
     */
    public function setTimezone(string $value): self
    {
        $this->timezone = DateValidator::validateTimezone($value);

        return $this;
    }

    /**
     * Removes the credentials for the current {@link User} along
     * with personal information other than "displayName".
     */
    public function erase(): void
    {
//        $this->setUsername('');
//        $this->setPassword('');
//        $this->setEmail('');
//        $this->setAvatar('');
//        $this->setActive(false);
//        $this->setRemoved(true);
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
        return $expiryDays !== self::NO_PASSWORD_EXPIRY &&
            time() >= strtotime(
                '+'.(int) $expiryDays.' days',
                $this->getPasswordModDate()->getTimestamp()
            );
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
            '/[a-z0-9!#\$%&\'*+\/=?^_`{|}~-]+'.
            '(?:\.[a-z0-9!#\$%&\'*+\/=?^_`{|}~-]+)'.
            '*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+'.
            '[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/',
            $this->email
        );
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            // $this->salt,
            $this->isActive,
        ]);
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized): void
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // $this->salt,
            $this->isActive) = unserialize($serialized);
    }

    /**
     * @return null
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
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

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}
