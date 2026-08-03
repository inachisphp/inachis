<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Theme;

/**
 * Feature registry for determining what features are available to themes
 */
final class FeatureRegistry
{
    /**
     * @var array<string, bool>
     */
    private array $features = [];

	/**
	 * Registers a feature by name
	 *
	 * @param string $feature
	 */
    public function register(string $feature): void
    {
        $feature = trim($feature);

        if ('' === $feature) {
            return;
        }

        $this->features[$feature] = true;
    }

	/**
	 * Returns the result of testing if a named feature is available
	 *
	 * @param string $feature
	 * @return bool
	 */
    public function has(string $feature): bool
    {
        return isset($this->features[$feature]);
    }

    /**
	 * Retuns an array of all feature names
     * @return array<string>
     */
    public function all(): array
    {
        return array_keys($this->features);
    }
}
