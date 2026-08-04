<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Security;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class RolePermission
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link RolePermission}
     */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;

    // Action enum: CREATE, EDIT, DELETE, VIEW
    #[ORM\Column(enumType: PermissionAction::class)]
    private PermissionAction $action;

    // Resource enum: PAGE, SERIES, IMAGE, TAG, CATEGORY
    #[ORM\Column(enumType: PermissionResource::class)]
    private PermissionResource $resource;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'rolePermissions')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Role $role;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getAction(): PermissionAction
    {
        return $this->action;
    }

    public function setAction(PermissionAction $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getResource(): PermissionResource
    {
        return $this->resource;
    }

    public function setResource(PermissionResource $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
