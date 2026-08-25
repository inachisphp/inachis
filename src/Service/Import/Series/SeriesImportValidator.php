<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Series;

use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Series\SeriesExportDto;
use Inachis\Repository\Content\PageRepository;

/**
 * Validator for importing series.
 *
 * Each warning entry is an array with keys:
 *   - 'type': one of 'link', 'create', 'missing', 'error'
 *   - 'title': the page title (or message)
 */
final class SeriesImportValidator
{
    /**
     * @var array<int, list<array{type: string, title: string}>>
     */
    private array $warnings = [];

    public function __construct(
        private ?PageRepository $pageRepository = null,
    ) {
    }

    /**
     * Validate an array of SeriesExportDto objects.
     *
     * @param SeriesExportDto[] $seriesList
     *
     * @return array<int, list<array{type: string, title: string}>> Warnings per series (by index)
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
     * @param int $index Index in the import list (for warnings)
     */
    public function validate(SeriesExportDto $dto, int $index): void
    {
        /** @var list<array{type: string, title: string}> $seriesWarnings */
        $seriesWarnings = [];

        if (empty($dto->title)) {
            $seriesWarnings[] = ['type' => 'error', 'title' => 'Series title is missing'];
        }

        if (empty($dto->url)) {
            $seriesWarnings[] = ['type' => 'error', 'title' => 'Series URL is missing'];
        }

        if (empty($dto->items)) {
            $seriesWarnings[] = ['type' => 'error', 'title' => 'Items list is empty'];
        } else {
            foreach ($dto->items as $item) {
                if ($item instanceof PageExportDto) {
                    if ($this->pageRepository) {
                        $candidates = $this->pageRepository->findBy(['title' => $item->title]);
                        $found = false;
                        foreach ($candidates as $candidate) {
                            if ($candidate->getSubTitle() !== $item->subTitle) {
                                continue;
                            }
                            if (!empty($item->urls)) {
                                $urls = $item->urls;
                                $dtoUrls = array_map(static fn ($u) => $u->path, $urls);
                                $matchUrl = false;
                                foreach ($candidate->getUrls() as $u) {
                                    if (in_array($u->getLink(), $dtoUrls, true)) {
                                        $matchUrl = true;
                                        break;
                                    }
                                }
                                if (!$matchUrl) {
                                    continue;
                                }
                            }
                            $found = true;
                            break;
                        }
                        $seriesWarnings[] = $found
                            ? ['type' => 'link', 'title' => $item->title]
                            : ['type' => 'create', 'title' => $item->title];
                    } else {
                        $seriesWarnings[] = ['type' => 'create', 'title' => $item->title];
                    }
                } else {
                    $itemString = (string) $item;
                    if ($this->pageRepository) {
                        $found = (null !== $this->pageRepository->findOneBy(['title' => $itemString]));
                        $seriesWarnings[] = $found
                            ? ['type' => 'link', 'title' => $itemString]
                            : ['type' => 'missing', 'title' => $itemString];
                    } else {
                        $seriesWarnings[] = ['type' => 'link', 'title' => $itemString];
                    }
                }
            }
        }

        // Always store warnings for series (even if only page-link info, not just errors)
        $this->warnings[$index] = $seriesWarnings;
    }
}
