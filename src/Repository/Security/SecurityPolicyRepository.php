<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Security;

use Inachis\Entity\Security\SecurityPolicy;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<SecurityPolicy>
 */
class SecurityPolicyRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityPolicy::class);
    }

    /**
     * Finds the active security policy.
     *
     * @return SecurityPolicy|null
     */
    public function findActive(): ?SecurityPolicy
    {
        return $this->findOneBy([
            'active' => true,
        ]);
    }
}
