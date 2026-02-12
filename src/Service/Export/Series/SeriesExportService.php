<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Series;

use Inachis\Repository\SeriesRepository;
use Inachis\Service\Export\Series\SeriesExportNormaliser;
use Symfony\Component\TaggedIterator\TaggedIterator;
use Inachis\Service\Export\AbstractExportService;

/**
 * Service for exporting series. The service uses the {@link SeriesRepository} to retrieve series,
 * and the {@link SeriesExportNormaliser} to normalise them. The service uses the {@link SeriesExportWriter}
 * interface to write the series to a file of a given type (JSON/MD/XML).
 */
final class SeriesExportService extends AbstractExportService
{
    /**
     * @param SeriesRepository $repository The repository to use for series operations.
     * @param SeriesExportNormaliser $normaliser The normaliser to use.
     * @param iterable<SeriesExportWriter> $writers The writers to use.
     */
    public function __construct(
        private SeriesRepository $repository,
        private SeriesExportNormaliser $normaliser,
        #[TaggedIterator('inachis.export_writer')] iterable $writers,
    ) {
        parent::__construct($writers);
    }

    /**
     * Export series to a file of a given type (JSON/MD/XML).
     *
     * @param iterable<Series> $series The series to export.
     * @param string $format The format to export to (json/md/xml).
     * @return string The exported series.
     */
    public function export(?iterable $series = null, string $format = 'json'): string
    {
        $series ??= $this->repository->findAll();
        return $this->exportCollection($series, $format, 'series');
    }

    /**
     * Normalise a series.
     *
     * @param object $series The series to normalise.
     * @return object The normalised series.
     */
    protected function normalise(object $series): object
    {
        return $this->normaliser->normalise($series);
    }

    /**
     * Get series by IDs via the repository.
     *
     * @param array $ids The IDs of the series to retrieve.
     * @return iterable<Series> The series.
     */
    public function getSeriesByIds(array $ids): iterable
    {
        return $this->repository->getFilteredIds($ids);
    }

    /**
     * Get all series via the repository.
     *
     * @return iterable<Series> The series.
     */
    public function getAllSeries(): iterable
    {
        return $this->repository->findAll();
    }
}
