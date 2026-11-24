<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

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
    ) {
    }
}
