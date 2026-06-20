<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum;

/**
 * Specifies the available change types for content changes
 */
enum DiffBlockType: string
{
    case UNCHANGED = 'unchanged';
    case INSERTED = 'inserted';
    case DELETED = 'deleted';
    case REPLACED = 'replaced';
}
