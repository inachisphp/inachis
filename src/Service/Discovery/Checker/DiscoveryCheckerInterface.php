<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
