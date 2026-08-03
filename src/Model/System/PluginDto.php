<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

final class PluginDto
{
    public string $identifier;

    public string $name;

    public string $version = '1.0.0';

    public string $author = '';

    public string $description = '';

    public string $homepage = '';

    public string $license = '';

    /**
     * Absolute path to the plugin directory.
     */
    public string $directory;

    /**
     * Absolute path to plugin.yaml.
     */
    public string $manifestPath;

    public string $twigNamespace = '';

    public string $twigPath = 'templates';

    /**
     * @var list<string>
     */
    public array $features = [];

    /**
     * @var array<string,mixed>
     */
    public array $requires = [];

    /**
     * @var array<string,mixed>
     */
    public array $suggests = [];
}
