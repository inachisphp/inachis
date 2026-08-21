<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Page;

use Inachis\Entity\Content\Page;
use Inachis\Repository\Content\PageRepository;
use Inachis\Service\Export\AbstractExportService;
use Inachis\Service\Export\ExportWriterInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Service for exporting pages. The service uses the {@link PageRepository} to retrieve pages,
 * and the {@link PageExportNormaliser} to normalise them. The service uses the {@link ExportWriterInterface}
 * interface to write the pages to a file of a given type (JSON/MD/XML).
 */
final class PageExportService extends AbstractExportService
{
    /**
     * @param PageRepository                 $pageRepository the repository to use for page operations
     * @param PageExportNormaliser           $normaliser     the normaliser to use
     * @param iterable<ExportWriterInterface> $writers        the writers to use
     */
    public function __construct(
        private PageRepository $pageRepository,
        private PageExportNormaliser $normaliser,
        #[AutowireIterator('inachis.export_writer')] iterable $writers,
    ) {
        parent::__construct($writers);
    }

    /**
     * Export pages to a file of a given type (JSON/MD/XML).
     *
     * @param iterable<Page>|null $pages  the pages to export
     * @param string              $format the format to export to (json/md/xml)
     *
     * @return string the exported pages
     */
    public function export(?iterable $pages = null, string $format = 'json'): string
    {
        $pages ??= $this->getAllPages();

        return $this->exportCollection($pages, $format);
    }

    /**
     * Normalise a page.
     *
     * @param object $page the page to normalise
     *
     * @return object the normalised page
     */
    protected function normalise(object $page): object
    {
        if (!$page instanceof Page) {
            throw new \InvalidArgumentException('Expected instance of '.Page::class);
        }

        return $this->normaliser->normalise($page);
    }

    /**
     * Get pages by IDs via the repository.
     *
     * @param list<string> $ids the IDs of the pages to retrieve
     *
     * @return iterable<Page> the pages
     */
    public function getPagesByIds(array $ids): iterable
    {
        return $this->pageRepository->getFilteredIds($ids);
    }

    /**
     * Get all pages via the repository.
     *
     * @return iterable<Page> the pages
     */
    public function getAllPages(): iterable
    {
        return $this->pageRepository->findAll();
    }

    /**
     * Get filtered pages via the repository.
     *
     * @param array{
     *     type?: string,
     *     categories?: array<string>,
     *     tags?: array<string>,
     *     status?: string,
     *     visible?: bool,
     *     keyword?: string,
     *     excludeIds?: list<string>,
     *     fromDate?: \DateTimeImmutable,
     *     toDate?: \DateTimeImmutable
     * } $filter the filter to use
     *
     * @return iterable<Page> the pages
     */
    public function getFilteredPages(array $filter): iterable
    {
        $type = isset($filter['type']) ? (string) $filter['type'] : '*';
        unset($filter['type']);

        return $this->pageRepository->getFilteredOfTypeByPostDate(
            array_filter($filter),
            $type,
            10000,
            0,
        );
    }

    /**
     * Get the count of all pages via the repository.
     *
     * @return int the count of pages
     */
    public function getAllCount(): int
    {
        return $this->pageRepository->getAllCount();
    }
}
