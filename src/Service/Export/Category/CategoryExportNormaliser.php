<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Export\Category;

use Inachis\Entity\Content\Category;
use Inachis\Model\CategoryExportDto;

/**
 * Normalises a category for export.
 */
final class CategoryExportNormaliser
{
    /**
     * Normalises a category for export.
     *
     * @param Category $category the category to normalise
     *
     * @return CategoryExportDto the normalised category
     */
    public function normalise(Category $category): CategoryExportDto
    {
        return CategoryExportDto::fromEntity($category);
    }
}
