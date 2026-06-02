<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

/**
 * Data Transfer Object for a navigation tab
 */
final class NavigationTabDto
{
    /**
     * Creates a new instance of {@link NavigationTabDto}
     * 
     * @param string $id The ID of the navigation tab
     * @param string $title The title of the navigation tab
     * @param string $url The URL of the navigation tab
     * @param int $position The position of the navigation tab
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $url,
        public readonly int $position
    ) {}

	/**
	 * Creates a new instance of {@link NavigationTabDto} from an array
	 * 
	 * @param array<string, string|int> $row The array representation of a {@link NavigationTabDto}
	 * @return self The {@link NavigationTabDto}
	 */
	public static function fromArray(array $row): self
	{
		return new self(
			(string) $row['id'],
			(string) $row['title'],
			(string) $row['url'],
			(int) $row['position']
		);
	}
}