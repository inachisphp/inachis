<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Checks the status of the site-map
 */
class SitemapChecker implements DiscoveryCheckerInterface
{
    /**
     * Constructor
     *
     * @param string $projectDir
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * Checkls the status of sitemap.xml to confirm it exists
     *
     * @return DiscoveryStatus
     */
    public function check(): DiscoveryStatus
    {
        $exists = file_exists(
            $this->projectDir . '/public/sitemap.xml'
        );

        return new DiscoveryStatus(
            'Sitemap',
            'Generated XML sitemap.',
            $exists ? 'success' : 'warning',
            '/sitemap.xml',
            $exists
                ? []
                : ['Sitemap has not been generated.'],
            'generated'
        );
    }
}
