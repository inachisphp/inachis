<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum;

enum ReviewStatus: string
{
	/**
	 * @const string Indicates a review is currently open
	 */
	case OPEN = 'open';

	/**
	 * @const string Indicates a Review has been resolved
	 */
	case RESOLVED = 'resolved';

	/**
	 * @const string Indicates a Review has been closed
	 */
	case CLOSED = 'closed';

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
			self::OPEN => 'open',
			self::RESOLVED => 'resolved',
			self::CLOSED => 'closed',
		};
	}
}
