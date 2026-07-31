<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

use Inachis\Build\ReleaseEntryType;

final readonly class ReleaseEntry
{
    public function __construct(
        public ReleaseEntryType $type,
        public string $path,
        public bool $optional = false,
    ) {}
}