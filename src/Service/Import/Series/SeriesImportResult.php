<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Import\Series;

/**
 * Result of a series import.
 */
final class SeriesImportResult
{
    public int $seriesImported = 0;

    public int $pagesLinked = 0;

    /** @var list<string> */
    public array $warnings = [];
}
