<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Series;

use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Export\AbstractExportService;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Service for exporting series. The service uses the {@link SeriesRepository} to retrieve series,
 * and the {@link SeriesExportNormaliser} to normalise them. The service uses the {@link SeriesExportWriter}
 * interface to write the series to a file of a given type (JSON/MD/XML).
 */
final class SeriesExportService extends AbstractExportService
{
    /**
     * @param SeriesRepository             $repository the repository to use for series operations
     * @param SeriesExportNormaliser       $normaliser the normaliser to use
     * @param iterable<SeriesExportWriter> $writers    the writers to use
     */
    public function __construct(
        private SeriesRepository $repository,
        private SeriesExportNormaliser $normaliser,
        #[AutowireIterator('inachis.export_writer')] iterable $writers,
    ) {
        parent::__construct($writers);
    }

    /**
     * Export series to a file of a given type (JSON/MD/XML).
     *
     * @param iterable<Series> $series the series to export
     * @param string           $format the format to export to (json/md/xml)
     *
     * @return string the exported series
     */
    public function export(?iterable $series = null, string $format = 'json'): string
    {
        $series ??= $this->repository->findAll();

        return $this->exportCollection($series, $format, 'series');
    }

    /**
     * Normalise a series.
     *
     * @param object $series the series to normalise
     *
     * @return object the normalised series
     */
    protected function normalise(object $series): object
    {
        return $this->normaliser->normalise($series);
    }

    /**
     * Get series by IDs via the repository.
     *
     * @param array $ids the IDs of the series to retrieve
     *
     * @return iterable<Series> the series
     */
    public function getSeriesByIds(array $ids): iterable
    {
        return $this->repository->getFilteredIds($ids);
    }

    /**
     * Get all series via the repository.
     *
     * @return iterable<Series> the series
     */
    public function getAllSeries(): iterable
    {
        return $this->repository->findAll();
    }
}
