<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Security;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Inachis\Entity\Security\RolePermission;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class Role
{
    /**
     * @var UuidInterface|null The unique identifier for the {@link Role}
     */
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected ?UuidInterface $id = null;


    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    // Flag to disable review stage for this role
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $disableReview = false;

    /**
     * @var Collection<int, RolePermission>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: RolePermission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $rolePermissions;

    public function __construct()
    {
        $this->rolePermissions = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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
}
