<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Category;

use Inachis\Model\CategoryExportDto;

/**
 * Validator for importing categories.
 */
final class CategoryImportValidator
{
    /**
     * @var array<int, array<string>>
     */
    private array $warnings = [];

    /**
     * Validate an array of CategoryExportDto objects.
     *
     * @param CategoryExportDto[] $categories
     * @return array<int, array<string>> Warnings per category (by index)
     */
    public function validateAll(array $categories): array
    {
        $this->warnings = [];

        foreach ($categories as $index => $categoryDto) {
            $this->validate($categoryDto, $index);
        }

        return $this->warnings;
    }

    /**
     * Validate a single CategoryExportDto.
     *
     * @param CategoryExportDto $dto
     * @param int $index
     */
    public function validate(CategoryExportDto $dto, int $index): void
    {
        $categoryWarnings = [];

        if (empty($dto->fullPath)) {
            $categoryWarnings[] = 'Full path is missing';
        }

        if (!is_bool($dto->visible)) {
            $categoryWarnings[] = 'Visible must be boolean';
        }

        if (!empty($dto->image) && !is_string($dto->image)) {
            $categoryWarnings[] = 'Image must be a string';
        }

        if (!empty($dto->icon) && !is_string($dto->icon)) {
            $categoryWarnings[] = 'Icon must be a string';
        }

        if (!empty($categoryWarnings)) {
            $this->warnings[$index] = $categoryWarnings;
        }
    }
}
