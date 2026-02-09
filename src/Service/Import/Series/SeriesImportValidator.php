<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Series;

use Inachis\Model\Series\SeriesExportDto;

/**
 * Validator for importing series.
 */
final class SeriesImportValidator
{
    /**
     * @var array<int, array<string>>
     */
    private array $warnings = [];

    /**
     * Validate an array of SeriesExportDto objects.
     *
     * @param SeriesExportDto[] $seriesList
     * @return array<int, array<string>> Warnings per series (by index)
     */
    public function validateAll(array $seriesList): array
    {
        $this->warnings = [];

        foreach ($seriesList as $index => $seriesDto) {
            $this->validate($seriesDto, $index);
        }

        return $this->warnings;
    }

    /**
     * Validate a single SeriesExportDto.
     *
     * @param SeriesExportDto $dto
     * @param int $index Index in the import list (for warnings)
     */
    public function validate(SeriesExportDto $dto, int $index): void
    {
        $seriesWarnings = [];

        if (empty($dto->title)) {
            $seriesWarnings[] = 'Title is missing';
        }

        if (empty($dto->url)) {
            $seriesWarnings[] = 'URL is missing';
        }

        if (!is_bool($dto->visibility)) {
            $seriesWarnings[] = 'Visibility must be boolean';
        }

        if (!empty($dto->items) && !is_array($dto->items)) {
            $seriesWarnings[] = 'Items must be an array of page titles';
        }

        if (!empty($seriesWarnings)) {
            $this->warnings[$index] = $seriesWarnings;
        }
    }
}
