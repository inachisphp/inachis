<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Security;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Inachis\Enum\Security\AuthenticationPolicy;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity]
class Role
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link Role}
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $identifier = '';

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    /** @var AuthenticationPolicy MFA requirement for administrators. */
    #[ORM\Column(enumType: AuthenticationPolicy::class)]
    private AuthenticationPolicy $authenticationPolicy = AuthenticationPolicy::PASSWORD_REQUIRED;

    // Flag to disable review stage for this role
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $disableReview = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $systemRole = false;

    /**
     * @var Collection<int, RolePermission>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: RolePermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rolePermissions;

    /** @var Collection<int, User> The users that have this role applied */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'assignedRoles')]
    private Collection $users;

    public function __construct()
    {
        $this->rolePermissions = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        if ('' === $this->identifier) {
            $this->identifier = $this->slugify($this->name);
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Returns the MFA requirement for this role.
     */
    public function getAuthenticationPolicy(): AuthenticationPolicy
    {
        return $this->authenticationPolicy;
    }

    /**
     * Sets the MFA requirement for this role.
     */
    public function setAuthenticationPolicy(
        AuthenticationPolicy $requirement,
    ): self {
        $this->authenticationPolicy = $requirement;

        return $this;
    }

    public function isDisableReview(): bool
    {
        return $this->disableReview;
    }

    public function setDisableReview(bool $disableReview): self
    {
        $this->disableReview = $disableReview;

        return $this;
    }

    /**
     * @return Collection<int, RolePermission>
     */
    public function getRolePermissions(): Collection
    {
        return $this->rolePermissions;
    }

    public function addRolePermission(RolePermission $permission): self
    {
        if (!$this->rolePermissions->contains($permission)) {
            $this->rolePermissions[] = $permission;
            $permission->setRole($this);
        }

        return $this;
    }

    public function removeRolePermission(RolePermission $permission): self
    {
        $this->rolePermissions->removeElement($permission);

        return $this;
    }

    /**
     * Returns the result of testing if this is a system-defined role.
     */
    public function isSystemRole(): bool
    {
        return $this->systemRole;
    }

    /**
     * Sets the flag of whether this is a system-defined role.
     */
    public function setSystemRole(bool $systemRole): self
    {
        $this->systemRole = $systemRole;

        return $this;
    }

    /**
     * Returns a Collection of the {@link User} entities assigned this role.
     *
     * @return Collection<int,User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    private function slugify(string $value): string
    {
        $slugger = new AsciiSlugger();
        $slug = $slugger
            ->slug($value)
            ->lower()
            ->toString();

        return trim($slug, '-');
    }

    /**
     * Determines if this is an administrator role.
     */
    public function isAdministrator(): bool
    {
        return in_array(
            strtolower($this->identifier),
            ['admin', 'administrator'],
            true,
        );
    }

    /**
     * Returns the number of users currently assigned this role.
     */
    public function getUserCount(): int
    {
        return $this->users->count();
    }

    /**
     * Determines whether the role can safely be deleted.
     */
    public function canBeDeleted(): bool
    {
        return !$this->systemRole
            && 0 === $this->getUserCount();
    }
}
