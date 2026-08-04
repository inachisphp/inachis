<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;

/**
 * Checls the status of the feed (should always be live).
 */
class RssChecker implements DiscoveryCheckerInterface
{
    /**
     * Returns default status of the RSS feed.
     */
    public function check(): DiscoveryStatus
    {
        return new DiscoveryStatus(
            'RSS Feed',
            'Latest published posts.',
            'success',
            '/feed',
            [],
            'generated',
        );
    }
}
