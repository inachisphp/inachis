<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Model\System;

final class PageMetadata
{
    public function __construct(
        public string $self = '',
        public string $tab = '',
        public string $title = '',
        public string $type = '',
        public string $description = '',
        public string $keywords = '',
        public string $updatedAt = '',
    ) {
    }
}
