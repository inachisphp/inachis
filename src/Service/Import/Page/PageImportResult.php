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
    /** @var int */
    public int $pagesImported = 0;

    /** @var int */
    public int $categoriesCreated = 0;

    /** @var int */
    public int $tagsCreated = 0;

    /** @var list<string> */
    public array $warnings = [];
}
