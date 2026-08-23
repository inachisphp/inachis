<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum;

enum EditorialStatus: string
{
    /**
     * Indicates a Page is currently a draft
     */
    case DRAFT = 'draft';

    /**
     * Indicates a Page is current in review
     */
    case REVIEW = 'review';

    /**
     * Indicates a Page has been published
     */
    case PUBLISHED = 'published';

    /**
     * Returns an array of all possible values for this enum.
     *
     * @return string[] An array of all possible values for this enum
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Returns the label for this enum value.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::REVIEW => 'In Review',
            self::PUBLISHED => 'Published',
        };
    }
}
