<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Import;

/**
 * Data Transfer Object for import options.
 */
final class ImportOptionsDto
{
    public bool $createMissingCategories = false;
    public bool $createMissingTags = false;
    public bool $overridePostDates = false;
}
