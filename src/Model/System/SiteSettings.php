<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

final class SiteSettings
{
    /**
     * Model used for site settings
     *
     * @param string $siteTitle
     * @param string $domain
     * @param list<string> $google
     * @param string $language
     * @param string $textDirection
     * @param string $abstract
     * @param bool $geotagContent
     */
    public function __construct(
        public string $siteTitle,
        public string $domain,
        public array $google,
        public string $language,
        public string $textDirection,
        public string $abstract,
        public bool $geotagContent,
        public string $displayTimezone,
    ) {}
}