<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Enum\System\PackageType;
use Inachis\Model\System\Plugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase
{
    #[Test]
    public function itInstantiatesPluginWithCorrectDefaultValues(): void
    {
        $plugin = new Plugin(
            'vendor/my-plugin',
            'My Plugin',
            '1.0.0',
            'Inachis Team',
            'A sample plugin for Inachis framework',
            'https://example.com/plugin',
            'MIT',
            '/path/to/plugin',
        );

        self::assertSame(PackageType::Plugin, $plugin->type);
        self::assertSame('vendor/my-plugin', $plugin->identifier);
        self::assertSame('My Plugin', $plugin->name);
        self::assertSame('1.0.0', $plugin->version);
        self::assertSame('Inachis Team', $plugin->author);
        self::assertSame('A sample plugin for Inachis framework', $plugin->description);
        self::assertSame('https://example.com/plugin', $plugin->homepage);
        self::assertSame('MIT', $plugin->license);
        self::assertSame('/path/to/plugin', $plugin->path);

        self::assertSame([], $plugin->features);
        self::assertNull($plugin->bootstrapClass);
    }

    #[Test]
    public function itAllowsSettingFeaturesAndBootstrapClass(): void
    {
        $plugin = new Plugin(
            'vendor/my-plugin',
            'My Plugin',
            '1.0.0',
            'Inachis Team',
            'A sample plugin for Inachis framework',
            'https://example.com/plugin',
            'MIT',
            '/path/to/plugin',
        );

        $plugin->features = ['feature1', 'feature2'];
        $plugin->bootstrapClass = 'Vendor\\MyPlugin\\Bootstrap';

        self::assertSame(['feature1', 'feature2'], $plugin->features);
        self::assertSame('Vendor\\MyPlugin\\Bootstrap', $plugin->bootstrapClass);
    }
}
