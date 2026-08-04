<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

/**
 * Model for detailing what constitutes a theme file.
 */
final class ThemeDto
{
    public string $identifier;

    public string $name;

    public string $version;

    public string $author;

    public string $description;

    /** @var string|null Minimal or semver string constraint for Inachis framework compatibility */
    public ?string $requiredInachisVersion = null;

    /** @var bool Flag indicating if the current framework version satisfies */
    public bool $isCompatible = true;

    /** @var list<string> */
    public array $requiredFeatures = [];

    /** @var list<string> */
    public array $suggestedFeatures = [];

    public string $path;

    public ?string $screenshot;

    /** @var bool Flag indicating if this instance is a fallback due to an issue */
    public bool $isFallback = false;

    /** @var string|null If fallback, stores the requested identifier that failed */
    public ?string $requestedIdentifier = null;

    /** @var string|null Reason for falling back (e.g., "incompatible_version", "not_found") */
    public ?string $fallbackReason = null;
}
