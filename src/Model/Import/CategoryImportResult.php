<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Import;

/**
 * Result of a category tree import.
 */
final class CategoryImportResult
{
    /** @var int */
    public int $categoriesCreated = 0;

    /** @var int */
    public int $categoriesUpdated = 0;

    /** @var array<string> */
    public array $warnings = [];
}
