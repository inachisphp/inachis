<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Export;

use Inachis\Repository\PageRepository;
use Inachis\Service\Export\AbstractExportService;
use Inachis\Service\Page\Export\PageExportNormaliser;
use Symfony\Component\TaggedIterator\TaggedIterator;

/**
 * Service for exporting pages. The service uses the {@link PageRepository} to retrieve pages,
 * and the {@link PageExportNormaliser} to normalise them. The service uses the {@link PageExportWriter}
 * interface to write the pages to a file of a given type (JSON/MD/XML).
 */
final class PageExportService extends AbstractExportService
{
    /**
     * @param $pageRepository The repository to use for page operations.
     * @param $normaliser The normaliser to use.
     * @param $writers The writers to use.
     */
    public function __construct(
        private PageRepository $pageRepository,
        private PageExportNormaliser $normaliser,
        #[TaggedIterator('inachis.export_writer')] iterable $writers,
    ) {
        parent::__construct($writers);
    }

    /**
     * Export pages to a file of a given type (JSON/MD/XML).
     *
     * @param iterable $pages The pages to export.
     * @param string $format The format to export to (json/md/xml).
     * @return string The exported pages.
     */
    public function export(?iterable $pages = null, string $format = 'json'): string
    {
        $pages ??= $this->getAllPages();
        return $this->exportCollection($pages, $format);
    }

    /**
     * Normalise a page.
     *
     * @param object $page The page to normalise.
     * @return object The normalised page.
     */
    protected function normalise(object $page): object
    {
        return $this->normaliser->normalise($page);
    }

    /**
     * Get pages by IDs via the repository.
     *
     * @param array $ids The IDs of the pages to retrieve.
     * @return iterable<Page> The pages.
     */
    public function getPagesByIds(array $ids): iterable
    {
        return $this->pageRepository->getFilteredIds($ids);
    }

    /**
     * Get all pages via the repository.
     *
     * @return iterable<Page> The pages.
     */
    public function getAllPages(): iterable
    {
        return $this->pageRepository->findAll();
    }

    /**
     * Get filtered pages via the repository.
     *
     * @param array $filter The filter to use.
     * @return iterable<Page> The pages.
     */
    public function getFilteredPages(array $filter): iterable
    {
        $filter_type = $filter['type'] ?? '*';
        unset($filter['type']);
        return $this->pageRepository->getFilteredOfTypeByPostDate(
            array_filter($filter),
            $filter_type,
            0,
            10000,
        );
    }

    /**
     * Get the count of all pages via the repository.
     *
     * @return int The count of pages.
     */
    public function getAllCount(): int
    {
        return $this->pageRepository->getAllCount();
    }
}
