<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

/**
 * Result of a page import.
 */
final class PageImportResult
{
    public int $pagesImported = 0;

    public int $categoriesCreated = 0;

    public int $tagsCreated = 0;

    /** @var list<string> */
    public array $warnings = [];
}
