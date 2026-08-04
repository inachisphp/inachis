<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Page;

/**
 * Data Transfer Object for URL.
 */
final class UrlDto
{
    public string $path;
    public bool $default = false;
}
