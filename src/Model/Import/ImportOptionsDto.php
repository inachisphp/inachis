<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
