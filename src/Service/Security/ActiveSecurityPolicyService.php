<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Security\SecurityPolicy;

class ActiveSecurityPolicyService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function getActivePolicy(): ?SecurityPolicy
    {
        return $this->entityManager->getRepository(SecurityPolicy::class)->findOneBy([
            'isActive' => true,
        ]);
    }
}
