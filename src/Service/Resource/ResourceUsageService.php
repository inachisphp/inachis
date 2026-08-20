<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Resource;

use Inachis\Entity\Media\AbstractFile;
use Inachis\Entity\Media\Download;
use Inachis\Entity\Media\Image;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;

class ResourceUsageService
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SeriesRepository $seriesRepository,
    ) {
    }

    /**
     * Returns an array of entities referencing the specified file resource.
     *
     * @return array{posts: mixed, series: mixed}
     */
    public function getUsages(AbstractFile $file): array
    {
        if ($file instanceof Image) {
            return [
                'posts' => $this->pageRepository->getPostsUsingImage($file),
                'series' => $this->seriesRepository->getSeriesUsingImage($file),
            ];
        }

        if ($file instanceof Download) {
            return [
                'posts' => [], //$this->pageRepository->getPostsUsingDownload($file),
                'series' => [], //$this->seriesRepository->getSeriesUsingDownload($file),
            ];
        }

        return [
            'posts' => [],
            'series' => [],
        ];
    }

    /**
     * Determines whether a file resource is actively referenced elsewhere.
     */
    public function isFileInUse(AbstractFile $file): bool
    {
        $usages = $this->getUsages($file);

        $postsCount = is_countable($usages['posts']) ? count($usages['posts']) : (!empty($usages['posts']) ? 1 : 0);
        $seriesCount = is_countable($usages['series']) ? count($usages['series']) : (!empty($usages['series']) ? 1 : 0);

        return ($postsCount + $seriesCount) > 0;
    }
}
