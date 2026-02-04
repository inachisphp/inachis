<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Import;

use Inachis\Model\Page\PageExportDto;

/**
 * Validator for importing pages.
 */
final class PageImportValidator
{
    /**
     * @var array<int, array<string>>
     */
    private array $warnings = [];

    /**
     * Validate an array of PageImportDto objects.
     *
     * @param PageImportDto[] $pages
     * @return array<int, array<string>> Warnings per page (by index)
     */
    public function validateAll(array $pages): array
    {
        $this->warnings = [];

        foreach ($pages as $index => $pageDto) {
            $this->validate($pageDto, $index);
        }

        return $this->warnings;
    }

    /**
     * Validate a single PageImportDto.
     *
     * @param PageImportDto $pageDto
     * @param int $index Index in the import list (for warnings)
     */
    public function validate(PageExportDto $pageDto, int $index): void
    {
        $pageWarnings = [];

        // Title is required
        if (empty($pageDto->title)) {
            $pageWarnings[] = 'Title is missing';
        }

        // Type must be valid
        if (!in_array($pageDto->type, ['post', 'page'], true)) {
            $pageWarnings[] = sprintf('Invalid type "%s"', $pageDto->type);
        }

        // Status must be valid
        if (!in_array($pageDto->status, ['draft', 'published'], true)) {
            $pageWarnings[] = sprintf('Invalid status "%s"', $pageDto->status);
        }

        // Check visibility
        if (!is_bool($pageDto->visibility)) {
            $pageWarnings[] = 'Visibility must be boolean';
        }

        // Check allowComments
        if (!is_bool($pageDto->allowComments)) {
            $pageWarnings[] = 'AllowComments must be boolean';
        }

        // Validate postDate if provided
        if (!empty($pageDto->postDate)) {
            try {
                new \DateTime($pageDto->postDate);
            } catch (\Exception $e) {
                $pageWarnings[] = sprintf('Invalid postDate "%s"', $pageDto->postDate);
            }
        }

        // Validate timezone if provided
        if (!empty($pageDto->timezone) && !in_array($pageDto->timezone, \DateTimeZone::listIdentifiers(), true)) {
            $pageWarnings[] = sprintf('Invalid timezone "%s"', $pageDto->timezone);
        }

        // Validate categories
        foreach ($pageDto->categories as $cat) {
            if (empty($cat->path)) {
                $pageWarnings[] = 'Category path cannot be empty';
            }
        }

        // Validate tags
        foreach ($pageDto->tags as $tag) {
            if (empty($tag->title)) {
                $pageWarnings[] = 'Tag title cannot be empty';
            }
        }

        if (!empty($pageWarnings)) {
            $this->warnings[$index] = $pageWarnings;
        }
    }
}