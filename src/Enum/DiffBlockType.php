<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Enum;

/**
 * Specifies the available change types for content changes.
 */
enum DiffBlockType: string
{
    case UNCHANGED = 'unchanged';
    case INSERTED = 'inserted';
    case DELETED = 'deleted';
    case REPLACED = 'replaced';
}
