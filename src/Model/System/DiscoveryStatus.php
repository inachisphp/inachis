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
     * @param string $title
     * @param string $description
     * @param string $status
     * @param string|null $url
     * @param array<string> $messages
     * @param string $group
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
     * Return true/false based on the status of the Discovery item
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->status === self::SUCCESS;
    }

    /**
     * Return the Material Icon text to use for the current status
     *
     * @return string
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