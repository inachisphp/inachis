<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
    /** @var UuidInterface Unique identifier for the {@link UserViewState} */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary')]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private UuidInterface $id;

    /** @var User The user this view state is for */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** @var string The context of the view state, e.g. post,page,image,download */
    #[ORM\Column(length: 100)]
    private string $context;

    /**
     * @var array<string,mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $state = [];

    /**
     * Constructor for {@link UserViewState}
     *
     * @param User $user
     * @param string $context
     */
    public function __construct(User $user, string $context)
    {
        $this->user = $user;
        $this->context = $context;
    }

    /**
     * Returns the current view state
     *
     * @return array<string,mixed>
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Sets the current view state
     *
     * @param array<string,mixed> $state
     * @return self
     */
    public function setState(array $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Gets the current context
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Gets the current user
     *
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
