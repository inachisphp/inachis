<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Page;

/**
 * Data Transfer Object for page export filters.
 */
final class PageExportFilterDto
{
    /**
     * @var string|null The type of page to filter by.
     */
    public ?string $type = null;

    /**
     * @var string|null The status of page to filter by.
     */
    public ?string $status = null;

    /**
     * @var string|null The language of page to filter by.
     */
    public ?string $language = null;

    /**
     * @var \DateTimeImmutable|null The from date to filter by.
     */
    public ?\DateTimeImmutable $fromDate = null;

    /**
     * @var \DateTimeImmutable|null The to date to filter by.
     */
    public ?\DateTimeImmutable $toDate = null;

    /**
     * @var string|null The category path to filter by.
     */
    public ?string $categoryPath = null;
}
