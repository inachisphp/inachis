<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Category;

use Inachis\Entity\Category;
use Inachis\Model\CategoryExportDto;

/**
 * Normalises a category for export.
 */
final class CategoryExportNormaliser
{
    /**
     * Normalises a category for export.
     *
     * @param Category $category The category to normalise.
     * @return CategoryExportDto The normalised category.
     */
    public function normalise(Category $category): CategoryExportDto
    {
        return CategoryExportDto::fromEntity($category);
    }
}