<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum\Security;

/**
 * Defines the minimum password strength requirement.
 *
 * The password validation service determines how each level
 * is evaluated.
 */
enum PasswordStrengthLevel: string
{
    /**
     * Basic validation only.
     *
     * Suitable for low-risk installations.
     */
    case STANDARD = 'standard';

    /**
     * Stronger validation requirements.
     *
     * Suitable for most administrative installations.
     */
    case STRONG = 'strong';

    /**
     * Highest password strength requirement.
     *
     * Intended for high-security environments.
     */
    case VERY_STRONG = 'very_strong';

    /**
     * Returns a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::STRONG => 'Strong',
            self::VERY_STRONG => 'Very Strong',
        };
    }
}
