<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Export;

use Inachis\Repository\PageRepository;
use Inachis\Service\Page\Export\PageExportNormaliser;

/**
 * Service for exporting pages. The service uses the {@link PageRepository} to retrieve pages,
 * and the {@link PageExportNormaliser} to normalise them. The service uses the {@link PageExportWriter} 
 * interface to write the pages to a file of a given type (JSON/MD/XML).
 */
final class PageExportService
{
    /** @var array<string, PageExportWriterInterface> */
    private array $writers = [];

    /**
     * @param $pageRepository The repository to use for page operations.
     * @param $normaliser The normaliser to use.
     * @param $writers The writers to use.
     */
    public function __construct(
        private PageRepository $pageRepository,
        private PageExportNormaliser $normaliser,
        #[TaggedIterator('inachis.page_export_writer')]
        iterable $writers,
    ) {
        foreach ($writers as $writer) {
            if (!$writer instanceof PageExportWriterInterface) {
                continue;
            }
            foreach (['json', 'md', 'xml'] as $format) {
                if ($writer->supports($format)) {
                    $this->writers[$format] = $writer;
                }
            }
        }
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
        if (!isset($this->writers[$format])) {
            throw new InvalidArgumentException(sprintf('Unsupported export format: %s', $format));
        }

        // If no pages provided, fetch all
        $pages ??= $this->pageRepository->findAll();

        // Normalize entities to DTOs
        $dtos = $this->normaliser->normaliseCollection($pages);

        // Serialize
        return $this->writers[$format]->write($dtos);
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
}
