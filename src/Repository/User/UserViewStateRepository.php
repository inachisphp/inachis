<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Repository\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserViewState;

/**
 * Repository for UserViewState.
 *
 * @extends ServiceEntityRepository<UserViewState>
 */
class UserViewStateRepository extends ServiceEntityRepository
{
    /**
     * Creates a new instance of the UserViewStateRepository.
     *
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserViewState::class);
    }

    /**
     * Finds user view state for context (e.g. Page) and User.
     */
    public function findFor(User $user, string $context): ?UserViewState
    {
        return $this->findOneBy([
            'user' => $user,
            'context' => $context,
        ]);
    }

    /**
     * Saves the current view state of this context.
     */
    public function save(UserViewState $state, bool $flush = true): void
    {
        $this->getEntityManager()->persist($state);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes the view state for this context.
     */
    public function remove(UserViewState $state, bool $flush = true): void
    {
        $this->getEntityManager()->remove($state);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
