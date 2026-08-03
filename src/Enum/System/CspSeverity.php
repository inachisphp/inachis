<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum\System;

/**
 * The severity of the CSP report
 */
enum CspSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Info = 'info';

    /**
     * Returns a label for the enum
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
            self::Info => 'Info',
        };
    }

    /**
     * Returns a CSS class-friendly name for the severity
     *
     * @return string
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Critical => 'critical',
            self::High => 'error',
            self::Medium => 'warning',
            self::Low => 'ok',
            self::Info => 'default',
        };
    }

    /**
     * The weighting for the severity
     *
     * @return int
     */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 100,
            self::High => 75,
            self::Medium => 50,
            self::Low => 25,
            self::Info => 0,
        };
    }

    /**
     * Does the CSP report need actioning?
     *
     * @return bool
     */
    public function isActionable(): bool
    {
        return match ($this) {
            self::Critical,
            self::High => true,

            self::Medium,
            self::Low,
            self::Info => false,
        };
    }
}
