<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Plugin;

use Inachis\Model\System\Plugin;
use Inachis\Service\Package\AbstractPackageScanner;

/**
 * @extends AbstractPackageScanner<Plugin>
 */
final readonly class PluginScanner extends AbstractPackageScanner
{
    /**
     * Cache prefix.
     */
    protected function cachePrefix(): string
    {
        return 'plugins';
    }

    /**
     * Plugin manifest filename.
     */
    protected function manifestFilename(): string
    {
        return 'plugin.yaml';
    }

    /**
     * Plugin directories.
     *
     * @return list<string>
     */
    protected function packageRoots(): array
    {
        return [
            $this->projectDir.'/plugins',
        ];
    }

    /**
     * Creates a plugin model from the manifest.
     *
     * @param array<string,mixed> $manifest
     */
    protected function createPackage(
        string $path,
        array $manifest,
    ): Plugin {
        $plugin = new Plugin(
            ...$this->createBasePackage(
                $path,
                $manifest,
            ),
        );

        $plugin->features = $this->extractFeatures(
            $manifest,
            'features',
        );

        $plugin->requiredFeatures = $this->extractFeatures(
            $manifest,
            'requires',
        );

        $plugin->suggestedFeatures = $this->extractFeatures(
            $manifest,
            'suggests',
        );

        return $plugin;
    }

    /**
     * Retrieves a list of plugins.
     *
     * @return list<Plugin>
     */
    public function getPlugins(): array
    {
        return $this->getPackages();
    }

    public function getPlugin(string $identifier): ?Plugin
    {
        return $this->getPackage($identifier);
    }

    /**
     * Rescan the plugin folder.
     *
     * @return list<Plugin>
     */
    public function rescanPlugins(): array
    {
        return $this->rescanPackages();
    }
}
