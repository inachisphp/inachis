<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater\Release;

final readonly class Manifest
{
    /**
     * @param list<string> $migrations
     * @param list<string> $preserve
     * @param list<string> $replace
     */
    public function __construct(
        public string $version,
        public string $minimumVersion,
        public string $package,
        public string $packageSha256,
        public array $migrations = [],
        public array $preserve = [],
        public array $replace = [],
        public ?string $archiveUrl = null,
        public string $type = 'core',
        public ?string $releaseNotes = null,
        public ?string $publishedAt = null,
    ) {}

    public function withArchiveUrl(string $url): self
    {
        return new self(
            version: $this->version,
            minimumVersion: $this->minimumVersion,
            package: $this->package,
            packageSha256: $this->packageSha256,
            migrations: $this->migrations,
            preserve: $this->preserve,
            replace: $this->replace,
            archiveUrl: $url,
            type: $this->type,
        );
    }
}
