<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Url;

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
    ) {
    }

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

        $content = "# {$title}";

        if ($description !== '') {
            $content .= "\n\n> {$description}";
        }

        $content .= <<<TXT


## Main pages

- /

TXT;

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
        $request = $this->requestStack->getCurrentRequest();

        $baseUrl = '';

        if ($request) {
            $baseUrl = $request->getSchemeAndHttpHost();
        }

        $resources = [];

        if (!preg_match('/^##\s+Resources$/mi', $content)) {
            $resources[] = '## Resources';
        }

        if (!preg_match('/^Sitemap:/mi', $content)) {
            $resources[] = sprintf(
                'Sitemap: %s/sitemap.xml',
                $baseUrl
            );
        }

        if (!preg_match('/^RSS:/mi', $content)) {
            $resources[] = sprintf(
                'RSS: %s/feed',
                $baseUrl
            );
        }

        if (!empty($resources)) {
            $content .= "\n\n" . implode("\n", $resources);
        }

        return rtrim($content) . PHP_EOL;
    }
}
