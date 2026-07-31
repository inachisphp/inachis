<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

final readonly class ReleaseDefinition
{
    /**
     * @param list<ReleaseEntry> $contents
     */
    public function __construct(
        public string $name,
        public array $contents,
        public array $persistent = [],
        /**
         * @param list<string> $commands
         */
        public array $commands = [],
        public bool $composerInstall = true,
        public bool $composerNoDev = true,
        public bool $composerOptimizeAutoloader = true,
    ) {}
}
