<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Discovery\Generator;

use Inachis\Repository\System\SettingRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generates security.txt
 */
class SecurityTxtGenerator
{
    /**
     * Constructor
     *
     * @param SettingRepository $settingRepository
     * @param RequestStack $requestStack
     */
    public function __construct(
        private readonly SettingRepository $settingRepository,
        protected RequestStack $requestStack,
    ) {}

    /**
     * Generate security.txt from stored value
     *
     * @return string
     */
    public function generate(): string
    {
        $content = trim(
            $this->settingRepository
                ->getValue('security_txt')
                ?? ''
        );

        if ($content === '') {
            return '';
        }

        return $this->appendCanonical($content);
    }


    /**
     * Add canonical URL if not already provided.
     *
     * @param string $content
     * @return string
     */
    private function appendCanonical(
        string $content
    ): string {
        if (preg_match(
            '/^Canonical:/mi',
            $content
        )) {
            return rtrim($content) . PHP_EOL;
        }

        return rtrim($content)
            . PHP_EOL . PHP_EOL
            . 'Canonical: '
            . $this->getCanonicalUrl()
            . PHP_EOL;
    }


    /**
     * Get canonical security.txt URL.
     *
     * @return string
     */
    private function getCanonicalUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request) {
            return $request->getSchemeAndHttpHost()
                . '/.well-known/security.txt';
        }

        return '/.well-known/security.txt';
    }
}
