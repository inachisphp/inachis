<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Security;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Enum\Security\AuthenticationPolicy;
use Inachis\Enum\Security\PasswordStrengthLevel;
use Inachis\Enum\Security\SensitiveAction;
use Inachis\Repository\Security\SecurityPolicyRepository;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the security policy enforced by the application.
 *
 * A security policy determines password requirements, authentication
 * requirements and step-up authentication behaviour. It does not describe
 * implementation details such as rate limiting, which should instead be
 * configured using Symfony's Security and RateLimiter components.
 *
 * Only one security policy should normally be active at any given time.
 */
#[Assert\Expression(
    expression: 'this.maximumPasswordLength === null or this.minimumPasswordLength <= this.maximumPasswordLength',
    message: 'Maximum password length cannot be smaller than minimum password length.'
)]
#[ORM\Entity(repositoryClass: SecurityPolicyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['active'])]
class SecurityPolicy
{
    public const DEFAULT_IDENTIFIER = 'default';

    public const STRICT_IDENTIFIER = 'strict';

    public const CUSTOM_IDENTIFIER = 'custom';

    /**
     * Unique identifier.
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    /**
     * Human-readable name of the policy.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private string $name = '';

    /**
     * Stable machine-readable identifier.
     * Built-in policies use a fixed identifier so they can always be
     * referenced regardless of their display name.
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9][a-z0-9_-]*$/',
        message: 'The identifier may only contain lowercase letters, numbers, underscores and hyphens.'
    )]
    #[ORM\Column(length: 50, unique: true)]
    private string $identifier = '';

    /**
     * Optional description explaining the purpose of this policy.
     */
    #[Assert\Length(max: 1000)]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Monotonically increasing version number.
     *
     * Increment whenever the effective behaviour of the policy changes.
     * This can be used for auditing and determining which policy version
     * was in effect when a user's credentials were created.
     */
    #[Assert\Positive]
    #[ORM\Column]
    private int $version = 1;

    /**
     * Minimum permitted password length.
     *
     * Modern password guidance recommends encouraging long passphrases
     * rather than enforcing arbitrary composition rules.
     */
    #[Assert\Positive]
    #[ORM\Column]
    private int $minimumPasswordLength = 14;

    /**
     * Maximum permitted password length.
     *
     * A value of null indicates that no application-imposed maximum exists.
     */
    #[Assert\Positive]
    #[ORM\Column(nullable: true)]
    private ?int $maximumPasswordLength = null;

    /**
     * Minimum password strength required.
     *
     * The password validation service determines how each strength level
     * is evaluated.
     */
    #[ORM\Column(enumType: PasswordStrengthLevel::class)]
    private PasswordStrengthLevel $passwordStrength =
        PasswordStrengthLevel::STANDARD;

    /**
     * Whether passwords found in known public data breaches should be rejected.
     */
    #[ORM\Column]
    private bool $rejectCompromisedPasswords = true;

    /**
     * Number of previously used passwords that cannot be reused.
     *
     * A value of zero disables password history enforcement.
     */
    #[Assert\PositiveOrZero]
    #[ORM\Column]
    private int $passwordReuseLimit = 5;

    /**
     * Minimum number of days a password must be retained before it may be
     * changed again.
     *
     * A value of null disables this restriction.
     */
    #[Assert\Positive]
    #[ORM\Column(nullable: true)]
    private ?int $minimumPasswordAgeDays = null;

    /**
     * Maximum password lifetime in days.
     *
     * A value of null indicates that passwords do not expire.
     *
     * Password expiry is generally discouraged by modern security guidance,
     * but remains available where organisational policies require it.
     */
    #[Assert\Positive]
    #[ORM\Column(nullable: true)]
    private ?int $passwordLifetimeDays = null;

    /**
     * Authentication requirements for administrator accounts.
     */
    #[ORM\Column(enumType: AuthenticationPolicy::class)]
    private AuthenticationPolicy $administratorPolicy =
        AuthenticationPolicy::MFA_REQUIRED;

    /**
     * Authentication requirements for super administrator accounts.
     */
    #[ORM\Column(enumType: AuthenticationPolicy::class)]
    private AuthenticationPolicy $superAdministratorPolicy =
        AuthenticationPolicy::WEBAUTHN_REQUIRED;

    /**
     * Whether step-up authentication is required for sensitive actions.
     *
     * When enabled, actions listed in {@see $stepUpRequiredActions} require
     * a recent authentication challenge before being performed.
     */
    #[ORM\Column]
    private bool $requireStepUpAuthentication = true;

    /**
     * Sensitive actions that require step-up authentication.
     * Values are stored as the string values of {@see SensitiveAction}.
     * 
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $stepUpRequiredActions = [
        SensitiveAction::ROLE_MANAGEMENT->value,
        SensitiveAction::SECURITY_CONFIGURATION_CHANGE->value,
        SensitiveAction::MFA_RESET->value,
    ];

    /**
     * Indicates whether this policy is managed by the application.
     *
     * Read-only policies cannot be modified through the administration
     * interface and are intended for built-in policies shipped with
     * the application.
     */
    #[ORM\Column]
    private bool $readOnly = false;

    /**
     * Indicates whether this is the currently active security policy.
     *
     * The application should ensure that only one policy is active at
     * any given time.
     */
    #[ORM\Column]
    private bool $active = false;

    /**
     * Date and time the policy was created.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * Date and time the policy was last modified.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $now = new DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function initialiseTimestamp(): void
    {
        $now = new DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

        /**
     * Get the unique identifier.
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * Get the policy name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the policy name.
     */
    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    /**
     * Get the identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Sets the identifier
     *
     * @param string $identifier
     * @return self
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = trim(strtolower($identifier));

        return $this;
    }

    /**
     * Get the policy description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set the policy description.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description !== null
            ? trim($description)
            : null;

        return $this;
    }

    /**
     * Get the policy version.
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Increment the policy version.
     */
    public function incrementVersion(): self
    {
        $this->version++;

        return $this;
    }

    /**
     * Get the minimum permitted password length.
     */
    public function getMinimumPasswordLength(): int
    {
        return $this->minimumPasswordLength;
    }

    /**
     * Set the minimum permitted password length.
     */
    public function setMinimumPasswordLength(int $length): self
    {
        if ($length < 1) {
            throw new \InvalidArgumentException(
                'Minimum password length must be positive.'
            );
        }

        if (
            $this->maximumPasswordLength !== null &&
            $length > $this->maximumPasswordLength
        ) {
            throw new \InvalidArgumentException(
                'Minimum password length cannot exceed maximum password length.'
            );
        }

        $this->minimumPasswordLength = $length;

        return $this;
    }

    /**
     * Get the maximum permitted password length.
     */
    public function getMaximumPasswordLength(): ?int
    {
        return $this->maximumPasswordLength;
    }

    /**
     * Set the maximum permitted password length.
     */
    public function setMaximumPasswordLength(?int $length): self
    {
        if (
            $length !== null &&
            $length < $this->minimumPasswordLength
        ) {
            throw new \InvalidArgumentException(
                'Maximum password length cannot be smaller than minimum password length.'
            );
        }

        $this->maximumPasswordLength = $length;

        return $this;
    }

    /**
     * Get the required password strength level.
     */
    public function getPasswordStrength(): PasswordStrengthLevel
    {
        return $this->passwordStrength;
    }

    /**
     * Set the required password strength level.
     */
    public function setPasswordStrength(
        PasswordStrengthLevel $strength
    ): self {
        $this->passwordStrength = $strength;

        return $this;
    }

    /**
     * Returns whether compromised passwords should be rejected.
     */
    public function getRejectCompromisedPasswords(): bool
    {
        return $this->rejectCompromisedPasswords;
    }

    /**
     * Set whether compromised passwords should be rejected.
     */
    public function setRejectCompromisedPasswords(
        bool $reject
    ): self {
        $this->rejectCompromisedPasswords = $reject;

        return $this;
    }

    /**
     * Get the number of previous passwords that cannot be reused.
     */
    public function getPasswordReuseLimit(): int
    {
        return $this->passwordReuseLimit;
    }

    /**
     * Set the password reuse limit.
     */
    public function setPasswordReuseLimit(int $limit): self
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException(
                'Password reuse limit cannot be negative.'
            );
        }

        $this->passwordReuseLimit = $limit;

        return $this;
    }

    /**
     * Get the minimum password age in days.
     */
    public function getMinimumPasswordAgeDays(): ?int
    {
        return $this->minimumPasswordAgeDays;
    }

    /**
     * Set the minimum password age in days.
     */
    public function setMinimumPasswordAgeDays(?int $days): self
    {
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException(
                'Minimum password age must be positive.'
            );
        }

        $this->minimumPasswordAgeDays = $days;

        return $this;
    }

    /**
     * Get the maximum password lifetime in days.
     */
    public function getPasswordLifetimeDays(): ?int
    {
        return $this->passwordLifetimeDays;
    }

    /**
     * Set the maximum password lifetime in days.
     */
    public function setPasswordLifetimeDays(?int $days): self
    {
        if ($days !== null && $days < 1) {
            throw new \InvalidArgumentException(
                'Password lifetime must be positive.'
            );
        }

        $this->passwordLifetimeDays = $days;

        return $this;
    }

    /**
     * Returns whether password expiry is enabled.
     */
    public function hasPasswordExpiry(): bool
    {
        return $this->passwordLifetimeDays !== null;
    }

    /**
     * Returns whether password history is enabled.
     */
    public function hasPasswordHistory(): bool
    {
        return $this->passwordReuseLimit > 0;
    }

    /**
     * Returns whether a maximum password length is configured.
     */
    public function hasMaximumPasswordLength(): bool
    {
        return $this->maximumPasswordLength !== null;
    }

    /**
     * Get administrator authentication requirements.
     */
    public function getAdministratorPolicy(): AuthenticationPolicy
    {
        return $this->administratorPolicy;
    }

    /**
     * Set administrator authentication requirements.
     */
    public function setAdministratorPolicy(
        AuthenticationPolicy $policy
    ): self {
        $this->administratorPolicy = $policy;

        return $this;
    }

    /**
     * Get super administrator authentication requirements.
     */
    public function getSuperAdministratorPolicy(): AuthenticationPolicy
    {
        return $this->superAdministratorPolicy;
    }

    /**
     * Set super administrator authentication requirements.
     */
    public function setSuperAdministratorPolicy(
        AuthenticationPolicy $policy
    ): self {
        $this->superAdministratorPolicy = $policy;

        return $this;
    }

    /**
     * Returns whether step-up authentication is required.
     */
    public function getRequireStepUpAuthentication(): bool
    {
        return $this->requireStepUpAuthentication;
    }

    /**
     * Set whether step-up authentication is required.
     */
    public function setRequireStepUpAuthentication(
        bool $required
    ): self {
        $this->requireStepUpAuthentication = $required;

        return $this;
    }

    /**
     * Get actions requiring step-up authentication.
     *
     * @return list<SensitiveAction>
     */
    public function getStepUpRequiredActions(): array
    {
        return array_map(
            static fn (string $action): SensitiveAction => SensitiveAction::from($action),
            $this->stepUpRequiredActions
        );
    }

    /**
     * Set actions requiring step-up authentication.
     *
     * @param list<SensitiveAction> $actions
     */
    public function setStepUpRequiredActions(
        array $actions
    ): self {
        $this->stepUpRequiredActions = array_map(
            static fn (SensitiveAction $action): string => $action->value,
            $actions
        );

        return $this;
    }

    /**
     * Add an action requiring step-up authentication.
     */
    public function addStepUpRequiredAction(
        SensitiveAction $action
    ): self {
        if (!in_array($action->value, $this->stepUpRequiredActions, true)) {
            $this->stepUpRequiredActions[] = $action->value;
        }

        return $this;
    }

    /**
     * Remove an action requiring step-up authentication.
     */
    public function removeStepUpRequiredAction(
        SensitiveAction $action
    ): self {
        $this->stepUpRequiredActions = array_values(
            array_filter(
                $this->stepUpRequiredActions,
                static fn (string $value): bool => $value !== $action->value
            )
        );

        return $this;
    }

    /**
     * Remove all step-up authentication requirements.
     */
    public function clearStepUpRequiredActions(): self
    {
        $this->stepUpRequiredActions = [];

        return $this;
    }

    /**
     * Determines whether an action requires step-up authentication.
     */
    public function requiresStepUpFor(
        SensitiveAction $action
    ): bool {
        if (!$this->requireStepUpAuthentication) {
            return false;
        }

        return in_array(
            $action->value,
            $this->stepUpRequiredActions,
            true
        );
    }

    /**
     * Returns whether this policy is read-only.
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Set whether this policy is read-only.
     */
    public function setReadOnly(bool $readOnly): self
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * Returns whether this policy is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Set whether this policy is active.
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get last update timestamp.
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
