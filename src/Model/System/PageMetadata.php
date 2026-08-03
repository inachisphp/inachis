<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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
    ) {}
}
