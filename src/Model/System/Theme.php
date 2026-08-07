<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

use Inachis\Enum\System\PackageType;

/**
 * Model for detailing what constitutes a theme file.
 */
final class Theme extends Package
{
    public function __construct(
        string $identifier,
        string $name,
        string $version,
        ?string $author,
        ?string $description,
        ?string $homepage,
        ?string $license,
        string $path,
    ) {
        parent::__construct(
            PackageType::Theme,
            $identifier,
            $name,
            $version,
            $author,
            $description,
            $homepage,
            $license,
            $path,
        );
    }

    /** @var string|null Absolute path to the screenshot */
    public ?string $screenshot = null;

    /** @var bool Flag indicating if this instance is a fallback due to an issue */
    public bool $isFallback = false;

    /** @var string|null If fallback, stores the requested identifier that failed */
    public ?string $requestedIdentifier = null;

    /** @var string|null Reason for falling back (e.g., "incompatible_version", "not_found") */
    public ?string $fallbackReason = null;
}
