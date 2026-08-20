<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Series;

/**
 * Data transfer object for exporting a series.
 */
final class SeriesExportDto
{
    /**
     * The string title of the series.
     */
    public string $title;
    /**
     * The string subtitle of the series.
     */
    public ?string $subTitle = null;
    /**
     * The string URL of the series.
     */
    public string $url;
    /**
     * The string description of the series.
     */
    public ?string $description = null;
    /**
     * The string first date of the series.
     */
    public ?string $firstDate = null;
    /**
     * The string last date of the series.
     */
    public ?string $lastDate = null;
    /**
     * The boolean visibility of the series.
     */
    public bool $visible;

    /**
     * @var array<int, string> The titles of the posts for series contents
     */
    public array $items = [];
}
