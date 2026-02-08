<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Category;

/**
 * Result of a category tree import.
 */
final class CategoryImportResult
{
    public int $categoriesCreated = 0;
    public int $categoriesUpdated = 0;
    public array $warnings = [];
}
