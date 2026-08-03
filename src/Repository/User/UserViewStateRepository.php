<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserViewState;

/**
 * Repository for UserViewState
 * 
 * @extends ServiceEntityRepository<UserViewState>
 */
class UserViewStateRepository extends ServiceEntityRepository
{
    /**
     * Creates a new instance of the WasteRepository
     * 
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserViewState::class);
    }

    /**
     * Finds user view state for context (e.g. Page) and User
     *
     * @param User $user
     * @param string $context
     * @return UserViewState|null
     */
    public function findFor(User $user, string $context): ?UserViewState
    {
        return $this->findOneBy([
            'user' => $user,
            'context' => $context,
        ]);
    }

    /**
     * Saves the current view state of this context
     *
     * @param UserViewState $state
     * @return void
     */
    public function save(UserViewState $state): void
    {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }
}
