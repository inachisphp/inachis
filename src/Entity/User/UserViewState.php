<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\User;

use Doctrine\DBAL\Types\Types;
use Inachis\Repository\User\UserViewStateRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: UserViewStateRepository::class)]
#[ORM\UniqueConstraint(columns: ['user_id', 'context'])]
class UserViewState
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $context;

    /**
     * @var array<string,mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $state = [];

    public function __construct(User $user, string $context)
    {
        $this->user = $user;
        $this->context = $context;
    }

    public function getState(): array
    {
        return $this->state;
    }

    public function setState(array $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
