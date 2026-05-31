<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum;

enum EditorialStatus: string
{
	/**
	 * @const string Indicates a Page is currently a draft
	 */
	case DRAFT = 'draft';

	/**
	 * @const string Indicates a Page is current in review
	 */
	case REVIEW = 'review';

	/**
	 * @const string Indicates a Page has been published
	 */
	case PUBLISHED = 'published';

	/**
	 * @const string Indicates a Page has been archived
	 */
	case ARCHIVED = 'archived';

	/**
	 * Returns an array of all possible values for this enum.
	 *
	 * @return string[] An array of all possible values for this enum
	 */
	public static function values(): array
	{
		return array_map(fn($case) => $case->value, self::cases());
	}

	/**
	 * Returns the label for this enum value.
	 *
	 * @return string
	 */
	public function label(): string
	{
		return match ($this) {
			self::DRAFT => 'Draft',
			self::REVIEW => 'In Review',
			self::PUBLISHED => 'Published',
			self::ARCHIVED => 'Archived',
		};
	}
}
