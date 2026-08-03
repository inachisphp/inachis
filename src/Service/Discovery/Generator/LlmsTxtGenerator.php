<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Generator;

use Inachis\Repository\System\SettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for generating the content of the llms.txt file.
 */
class LlmsTxtGenerator
{
    /**
     * Construct the generator.
     *
     * @param SettingRepository $settingRepository
     * @param RequestStack $requestStack
     */
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Generate the llms.txt content.
     *
     * @return string
     */
    public function generate(): string
    {
        $content = trim(
            $this->settingRepository->getValue('llms_txt') ?? ''
        );

        if ($content === '') {
            $content = $this->generateDefault();
        }

        return $this->appendResources($content);
    }

    /**
     * Generate a default llms.txt document.
     *
     * @return string
     */
    private function generateDefault(): string
    {
        $title = trim(
            $this->settingRepository->getValue('site_title')
            ?? 'Website'
        );

        $description = trim(
            $this->settingRepository->getValue('site_description')
            ?? ''
        );

        $content = sprintf(
            "# %s",
            $title
        );

        if ($description !== '') {
            $content .= sprintf(
                "\n\n> %s",
                $description
            );
        }

        $content .= "\n\n## Main pages\n\n- /\n";

        return $content;
    }

    /**
     * Append standard resources if they are not already present.
     *
     * @param string $content
     * @return string
     */
    private function appendResources(string $content): string
    {
        $resources = [];

        if (!preg_match('/^##\s+Resources$/mi', $content)) {
            $resources[] = '## Resources';
        }

        $sitemap = $this->getSitemapUrl();

        if (!preg_match('/^Sitemap:/mi', $content)) {
            $resources[] = sprintf('Sitemap: %s', $sitemap);
        }

        $feed = $this->getFeedUrl();
        if (!preg_match('/^RSS:/mi', $content)) {
            $resources[] = sprintf('RSS: %s', $feed);
        }

        if ($resources !== []) {
            $content .= "\n\n" . implode(
                "\n",
                $resources
            );
        }

        return rtrim($content) . PHP_EOL;
    }

    /**
     * Get the sitemap URL.
     *
     * @return string
     */
    private function getSitemapUrl(): string
    {
        return $this->getBaseUrl() . '/sitemap.xml';
    }

    /**
     * Get the RSS feed URL if available.
     *
     * @return string|null
     */
    private function getFeedUrl(): ?string
    {
        return $this->getBaseUrl() . '/feed';
    }

    /**
     * Get the current site base URL.
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            return $request->getSchemeAndHttpHost();
        }

        return '';
    }
}
