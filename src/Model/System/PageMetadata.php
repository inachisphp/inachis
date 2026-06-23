<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
        public string $modDate = '',
    ) {}
}
