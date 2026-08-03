<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

use Inachis\Enum\System\PackageType;
use Inachis\Model\System\Package;

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
