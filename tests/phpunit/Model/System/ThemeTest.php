<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Enum\System\PackageType;
use Inachis\Model\System\Theme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ThemeTest extends TestCase
{
    #[Test]
    public function itCreatesAThemeWithPackageValues(): void
    {
        $theme = new Theme(
            identifier: 'default',
            name: 'Default Theme',
            version: '1.0.0',
            author: 'Inachis',
            description: 'The default Inachis theme.',
            homepage: 'https://example.com',
            license: 'MIT',
            path: '/themes/default',
        );

        self::assertSame(PackageType::Theme, $theme->type);
        self::assertSame('default', $theme->identifier);
        self::assertSame('Default Theme', $theme->name);
        self::assertSame('1.0.0', $theme->version);
        self::assertSame('Inachis', $theme->author);
        self::assertSame(
            'The default Inachis theme.',
            $theme->description,
        );
        self::assertSame('https://example.com', $theme->homepage);
        self::assertSame('MIT', $theme->license);
        self::assertSame('/themes/default', $theme->path);
    }

    #[Test]
    public function itUsesNullValuesForOptionalPackageProperties(): void
    {
        $theme = new Theme(
            identifier: 'minimal',
            name: 'Minimal',
            version: '2.0.0',
            author: null,
            description: null,
            homepage: null,
            license: null,
            path: '/themes/minimal',
        );

        self::assertSame(PackageType::Theme, $theme->type);
        self::assertSame('minimal', $theme->identifier);
        self::assertSame('Minimal', $theme->name);
        self::assertSame('2.0.0', $theme->version);
        self::assertNull($theme->author);
        self::assertNull($theme->description);
        self::assertNull($theme->homepage);
        self::assertNull($theme->license);
        self::assertSame('/themes/minimal', $theme->path);
    }

    #[Test]
    public function itUsesDefaultThemeProperties(): void
    {
        $theme = new Theme(
            identifier: 'default',
            name: 'Default',
            version: '1.0.0',
            author: null,
            description: null,
            homepage: null,
            license: null,
            path: '/themes/default',
        );

        self::assertNull($theme->screenshot);
        self::assertFalse($theme->isFallback);
        self::assertNull($theme->requestedIdentifier);
        self::assertNull($theme->fallbackReason);
    }

    #[Test]
    public function itAllowsThemeFallbackPropertiesToBeSet(): void
    {
        $theme = new Theme(
            identifier: 'fallback',
            name: 'Fallback',
            version: '1.0.0',
            author: null,
            description: null,
            homepage: null,
            license: null,
            path: '/themes/fallback',
        );

        $theme->screenshot = '/themes/fallback/screenshot.png';
        $theme->isFallback = true;
        $theme->requestedIdentifier = 'missing-theme';
        $theme->fallbackReason = 'not_found';

        self::assertSame(
            '/themes/fallback/screenshot.png',
            $theme->screenshot,
        );
        self::assertTrue($theme->isFallback);
        self::assertSame('missing-theme', $theme->requestedIdentifier);
        self::assertSame('not_found', $theme->fallbackReason);
    }
}
