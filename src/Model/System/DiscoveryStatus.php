<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

/**
 * Represents the status of a discovery resource.
 */
class DiscoveryStatus
{
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';

    /**
     * @param array<string> $messages
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $status,
        public readonly ?string $url = null,
        public readonly array $messages = [],
        public readonly string $group = 'documents',
    ) {
    }

    /**
     * Return true/false based on the status of the Discovery item.
     */
    public function isHealthy(): bool
    {
        return self::SUCCESS === $this->status;
    }

    /**
     * Return the Material Icon text to use for the current status.
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            self::SUCCESS => 'check_circle',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            default => 'help',
        };
    }
}
