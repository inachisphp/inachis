<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Model\System\SiteSettings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SiteSettingsTest extends TestCase
{
    #[Test]
    public function itCreatesSiteSettings(): void
    {
        $google = [
            'analytics' => 'G-123456789',
        ];

        $settings = new SiteSettings(
            siteTitle: 'Inachis',
            domain: 'example.com',
            google: $google,
            language: 'en',
            textDirection: 'ltr',
            abstract: 'A test site.',
            geotagContent: true,
            displayTimezone: 'Europe/London',
        );

        self::assertSame('Inachis', $settings->siteTitle);
        self::assertSame('example.com', $settings->domain);
        self::assertSame($google, $settings->google);
        self::assertSame('en', $settings->language);
        self::assertSame('ltr', $settings->textDirection);
        self::assertSame('A test site.', $settings->abstract);
        self::assertTrue($settings->geotagContent);
        self::assertSame('Europe/London', $settings->displayTimezone);
    }

    #[Test]
    public function itSupportsEmptyGoogleSettingsAndDisabledGeotagging(): void
    {
        $settings = new SiteSettings(
            siteTitle: 'Test',
            domain: 'localhost',
            google: [],
            language: 'en-GB',
            textDirection: 'ltr',
            abstract: '',
            geotagContent: false,
            displayTimezone: 'UTC',
        );

        self::assertSame('Test', $settings->siteTitle);
        self::assertSame('localhost', $settings->domain);
        self::assertSame([], $settings->google);
        self::assertSame('en-GB', $settings->language);
        self::assertSame('ltr', $settings->textDirection);
        self::assertSame('', $settings->abstract);
        self::assertFalse($settings->geotagContent);
        self::assertSame('UTC', $settings->displayTimezone);
    }
}
