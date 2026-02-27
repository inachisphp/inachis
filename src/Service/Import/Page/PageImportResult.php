<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Page;

/**
 * Result of a page import.
 */
final class PageImportResult
{
    public int $pagesImported = 0;
    public int $categoriesCreated = 0;
    public int $tagsCreated = 0;
    public array $warnings = [];
}
