<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Import;

final class ImportOptionsDto
{
    public bool $createMissingCategories = false;
    public bool $createMissingTags = false;
    public bool $overridePostDates = false;
}