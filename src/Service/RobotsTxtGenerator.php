<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use Inachis\Repository\SettingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service for generating the content of the robots.txt file based on stored settings
 */
class RobotsTxtGenerator
{
	/**
	 * Construct the generator with the required dependencies
	 *
	 * @param SettingRepository $settingRepository
	 * @param UrlGeneratorInterface $urlGenerator
	 */
	public function __construct(
		private readonly SettingRepository $settingRepository,
		private readonly UrlGeneratorInterface $urlGenerator
	) {
	}

	/**
	 * Generate the content of the robots.txt file based on the stored configuration
	 *
	 * @todo Add in sitemap once we have a sitemap generator
	 * @return string
	 */
    public function generate(): string
    {
        $robotsTxt = trim(
            $this->settingRepository->getValue('robots_txt')
            ?? 'User-agent: *'
        );

        // $sitemapUrl = $this->urlGenerator->generate(
        //     'inachis_sitemap',
        //     [],
        //     UrlGeneratorInterface::ABSOLUTE_URL
        // );

        $content = $robotsTxt;

        // if (!str_contains($robotsTxt, 'Sitemap:')) {
        //     $content .= "\n\nSitemap: " . $sitemapUrl;
        // }

        return $content;
    }
}
