<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Import;

/**
 * Data Transfer Object for import options
 */
final class ImportOptionsDto
{
    /**
     * @var bool
     */
    public bool $createMissingCategories = false;
    /**
     * @var bool
     */
    public bool $createMissingTags = false;
    /**
     * @var bool
     */
    public bool $overridePostDates = false;
}
