<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */


namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;

/**
 * Checls the status of the feed (should always be live)
 */
class RssChecker implements DiscoveryCheckerInterface
{
    /**
     * Returns default status of the RSS feed
     *
     * @return DiscoveryStatus
     */
    public function check(): DiscoveryStatus
    {
        return new DiscoveryStatus(
            'RSS Feed',
            'Latest published posts.',
            'success',
            '/feed',
            [],
            'generated'
        );
    }
}
