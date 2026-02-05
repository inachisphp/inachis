<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Series\Export;

use Inachis\Repository\SeriesRepository;

final class SeriesExportService
{
    public function __construct(
        private SeriesRepository $repository,
        private SeriesExportNormalizer $normalizer,
    ) {}

    public function getAll(): iterable
    {
        foreach ($this->repository->findAll() as $series) {
            yield $this->normalizer->normalize($series);
        }
    }
}
