<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service;

use Inachis\Repository\System\SettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for generating the content of the robots.txt file based on stored settings
 */
class RobotsTxtGenerator
{
	/**
	 * Construct the generator with the required dependencies
	 *
	 * @param SettingRepository $settingRepository
	 * @param RequestStack $requestStack
	 */
	public function __construct(
		private readonly SettingRepository $settingRepository,
    	private readonly RequestStack $requestStack,
	){}

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

		$request = $this->requestStack->getCurrentRequest();
		$sitemapUrl = '/sitemap.xml';
        if ($request) {
    		$sitemapUrl = $request->getSchemeAndHttpHost() . '/sitemap.xml';
		}
        $content = $robotsTxt;

        if (!str_contains($robotsTxt, 'Sitemap:')) {
            $content .= "\n\nSitemap: " . $sitemapUrl;
        }

        return $content;
    }
}
