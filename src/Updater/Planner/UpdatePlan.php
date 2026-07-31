<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater\Planner;

final readonly class UpdatePlan
{
    /**
     * @param list<string> $replacePaths
     * @param list<string> $preservePaths
     * @param list<string> $migrations
     */
    public function __construct(
        public string $currentVersion,
        public string $targetVersion,
        public string $package,
        public ?string $archiveUrl,
        public array $replacePaths,
        public array $preservePaths,
        public array $migrations,
        public bool $requiresMigration,
        public string $type = 'core',
    ) {}
}
