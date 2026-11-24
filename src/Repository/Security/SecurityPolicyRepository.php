<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Repository\Security;

use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Security\SecurityPolicy;
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
     */
    public function findActive(): ?SecurityPolicy
    {
        return $this->findOneBy([
            'active' => true,
        ]);
    }
}
