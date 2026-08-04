<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Page;

use Inachis\Enum\DiffBlockType;

/**
 * DTO used for handling changes between {@link Page} revisions when
 * displaying the {@link Revision}.
 */
final readonly class DiffBlock
{
    public function __construct(
        public DiffBlockType $type,
        public string $html,
        public ?string $oldHtml = null,
    ) {
    }
}
