<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Security\SecurityPolicy;

class ActiveSecurityPolicyService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getActivePolicy(): ?SecurityPolicy
    {
        return $this->entityManager->getRepository(SecurityPolicy::class)->findOneBy([
            'isActive' => true,
        ]);
    }
}
