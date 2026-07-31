<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

final readonly class ReleaseWorkspace
{
    public function __construct(
        public string $path,
        public ReleaseDefinition $definition,
        public array $metadata = [],
    ) {}
}
