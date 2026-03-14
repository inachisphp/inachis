<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

final class NavigationTabDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $url,
        public readonly int $position
    ) {}

	public static function fromArray(array $row): self
	{
		return new self(
			(string) $row['id'],
			$row['title'],
			$row['url'],
			(int) $row['position']
		);
	}
}