<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

/**
 * Model for detailing what constitutes a theme file
 */
final class ThemeDto
{
	/** @var string */
    public string $slug;
	/** @var string */
    public string $name;
	/** @var string */
    public string $version;
	/** @var string */
    public string $author;
	/** @var string */
    public string $description;

	/** @var list<string> */
    public array $requiredFeatures = [];
	/** @var list<string> */
    public array $suggestedFeatures = [];

	/** @var string|null */
    public string $path;
	/** @var string|null */
    public ?string $screenshot;
}
