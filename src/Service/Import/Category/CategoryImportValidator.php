<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
     *
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
     */
    public function validate(CategoryExportDto $dto, int $index): void
    {
        $categoryWarnings = [];

        if (empty($dto->fullPath)) {
            $categoryWarnings[] = 'Full path is missing';
        }

        if (!empty($categoryWarnings)) {
            $this->warnings[$index] = $categoryWarnings;
        }
    }
}
