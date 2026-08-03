<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

use Inachis\Enum\System\PackageType;

/**
 * Defines the default structure for packages that Updater can use.
 */
abstract class Package
{
    public function __construct(
        /** @var PackageType The type of package (core/theme/plugin) */
        public readonly PackageType $type,

	    /** @var string */
        public string $identifier,
        
	    /** @var string */
        public string $name,
        
	    /** @var string */
        public string $version,
        
	    /** @var string */
        public ?string $author,
        
	    /** @var string */
        public ?string $description,
        
	    /** @var string */
        public ?string $homepage,
        
	    /** @var string */
        public ?string $license,
        
	    /** @var string Absolute path to the package. */
        public string $path,
    ) {}

    /**
     * Minimal framework version required.
     */
    public ?string $requiredInachisVersion = null;

    /**
     * Whether this package is compatible with the running version.
     */
    public bool $isCompatible = true;

    /**
     * @var list<string>
     */
    public array $requiredFeatures = [];

    /**
     * @var list<string>
     */
    public array $suggestedFeatures = [];
}
