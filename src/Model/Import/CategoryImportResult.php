<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Import;

/**
 * Result of a category tree import.
 */
final class CategoryImportResult
{
    public int $categoriesCreated = 0;

    public int $categoriesUpdated = 0;

    /** @var array<string> */
    public array $warnings = [];
}
