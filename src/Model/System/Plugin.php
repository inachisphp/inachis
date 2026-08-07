<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

use Inachis\Enum\System\PackageType;

final class Plugin extends Package
{
    public function __construct(
        string $identifier,
        string $name,
        string $version,
        string $author,
        string $description,
        string $homepage,
        string $license,
        string $path,
    ) {
        parent::__construct(
            PackageType::Plugin,
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

    /**
     * @var list<string>
     */
    public array $features = [];

    /**
     * Fully-qualified bootstrap class, if applicable.
     */
    public ?string $bootstrapClass = null;
}
