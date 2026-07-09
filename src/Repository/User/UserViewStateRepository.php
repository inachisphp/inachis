<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserViewState;

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

    public function findFor(User $user, string $context): ?UserViewState
    {
        return $this->findOneBy([
            'user' => $user,
            'context' => $context,
        ]);
    }

    public function save(UserViewState $state): void
    {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }
}
