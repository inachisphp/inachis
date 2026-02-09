<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Series;

/**
 * Result of a series import.
 */
final class SeriesImportResult
{
    public int $seriesImported = 0;
    public int $pagesLinked = 0;
    public array $warnings = [];
}
