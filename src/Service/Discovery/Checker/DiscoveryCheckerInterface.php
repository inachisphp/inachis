<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface used by Discovery status checks
 */
#[AutoconfigureTag('inachis.discovery_checker')]
interface DiscoveryCheckerInterface
{
    public function check(): DiscoveryStatus;
}
