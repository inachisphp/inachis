<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Model\System;

final class SiteSettings
{
    /**
     * Model used for site settings.
     *
     * @param list<string> $google
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
    ) {
    }
}
