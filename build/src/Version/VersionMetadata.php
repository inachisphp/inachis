<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Version;

final readonly class VersionMetadata
{
	public function __construct(
		public string $version,
		public string $commit,
		public string $buildDate,
	) {}

	/**
	 * @return array<string, string>
	 */
	public function toArray(): array
	{
		return [
			'version' => $this->version,
			'commit' => $this->commit,
			'build_date' => $this->buildDate,
		];
	}
}
