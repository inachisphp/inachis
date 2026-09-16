<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

use DateTimeImmutable;

final readonly class BackupManifest
{
    /**
     * @param array<string, string> $inachis
     */
    public function __construct(
        public int $formatVersion,
        public array $inachis,
        public DateTimeImmutable $createdAt,
        public string $phpVersion,
        public string $databasePlatform,
        public string $databaseVersion,
        public string $migrationVersion,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'format_version' => $this->formatVersion,
            'inachis' => $this->inachis,
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'php_version' => $this->phpVersion,
            'database' => [
                'platform' => $this->databasePlatform,
                'version' => $this->databaseVersion,
            ],
            'migrations' => [
                'version' => $this->migrationVersion,
            ],
        ];
    }

    public function toJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }
}
