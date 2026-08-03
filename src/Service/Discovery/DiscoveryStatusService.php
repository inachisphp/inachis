<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery;

use Inachis\Model\System\DiscoveryStatus;
use Inachis\Service\Discovery\Checker\DiscoveryCheckerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Collects discovery health information from registered checkers.
 */
class DiscoveryStatusService
{
    /**
     * @param iterable<DiscoveryCheckerInterface> $checkers
     */
    public function __construct(
        #[AutowireIterator('inachis.discovery_checker')]
        private readonly iterable $checkers
    ) {
    }

    /**
     * @return array<DiscoveryStatus>
     */
    public function getStatus(): array
    {
        $status = [];

        foreach ($this->checkers as $checker) {
            $status[] = $checker->check();
        }

        return $status;
    }

    /**
     * Returns site discoery status by groups
     *
     * @return array
     */
    public function getGroupedStatus(): array
    {
        $grouped = [];

        foreach ($this->getStatus() as $status) {
            $grouped[$status->group][] = $status;
        }

        return $grouped;
    }
}
