<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater\Release;

use Inachis\Updater\Release\Manifest;
use PHPUnit\Framework\TestCase;

class ManifestTest extends TestCase
{
    public function testConstructorInitializesRequiredPropertiesWithDefaults(): void
    {
        $manifest = new Manifest(
            version: '2.0.0',
            minimumVersion: '1.5.0',
            package: 'inachis-2.0.0.zip',
            packageSha256: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        );

        $this->assertSame('2.0.0', $manifest->version);
        $this->assertSame('1.5.0', $manifest->minimumVersion);
        $this->assertSame('inachis-2.0.0.zip', $manifest->package);
        $this->assertSame('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', $manifest->packageSha256);

        // Verify default property values
        $this->assertSame([], $manifest->migrations);
        $this->assertSame([], $manifest->preserve);
        $this->assertSame([], $manifest->replace);
        $this->assertNull($manifest->archiveUrl);
        $this->assertSame('core', $manifest->type);
        $this->assertNull($manifest->releaseNotes);
        $this->assertNull($manifest->publishedAt);
    }

    public function testConstructorInitializesAllPropertiesWithCustomValues(): void
    {
        $manifest = new Manifest(
            version: '2.1.0',
            minimumVersion: '2.0.0',
            package: 'inachis-2.1.0.tar.gz',
            packageSha256: 'a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e',
            migrations: ['Version20260101000000.php'],
            preserve: ['config/custom.yaml'],
            replace: ['bin/console'],
            archiveUrl: 'https://example.com/2.1.0.tar.gz',
            type: 'plugin',
            releaseNotes: 'Performance improvements and bug fixes.',
            publishedAt: '2026-08-10T10:00:00Z',
        );

        $this->assertSame('2.1.0', $manifest->version);
        $this->assertSame('2.0.0', $manifest->minimumVersion);
        $this->assertSame('inachis-2.1.0.tar.gz', $manifest->package);
        $this->assertSame('a591a6d40bf420404a011733cfb7b190d62c65bf0bcda32b57b277d9ad9f146e', $manifest->packageSha256);
        $this->assertSame(['Version20260101000000.php'], $manifest->migrations);
        $this->assertSame(['config/custom.yaml'], $manifest->preserve);
        $this->assertSame(['bin/console'], $manifest->replace);
        $this->assertSame('https://example.com/2.1.0.tar.gz', $manifest->archiveUrl);
        $this->assertSame('plugin', $manifest->type);
        $this->assertSame('Performance improvements and bug fixes.', $manifest->releaseNotes);
        $this->assertSame('2026-08-10T10:00:00Z', $manifest->publishedAt);
    }

    public function testWithArchiveUrlReturnsNewInstanceWithUpdatedUrl(): void
    {
        $original = new Manifest(
            version: '2.0.0',
            minimumVersion: '1.5.0',
            package: 'inachis-2.0.0.zip',
            packageSha256: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            migrations: ['Version1.php'],
            preserve: ['config/'],
            replace: ['src/'],
            archiveUrl: null,
            type: 'core',
        );

        $newUrl = 'https://example2.com/downloads/inachis-2.0.0.zip';
        $updated = $original->withArchiveUrl($newUrl);

        // Ensure immutability (returns new object)
        $this->assertNotSame($original, $updated);

        // Original object remains unchanged
        $this->assertNull($original->archiveUrl);

        // New object has updated archiveUrl
        $this->assertSame($newUrl, $updated->archiveUrl);

        // All other properties are retained
        $this->assertSame($original->version, $updated->version);
        $this->assertSame($original->minimumVersion, $updated->minimumVersion);
        $this->assertSame($original->package, $updated->package);
        $this->assertSame($original->packageSha256, $updated->packageSha256);
        $this->assertSame($original->migrations, $updated->migrations);
        $this->assertSame($original->preserve, $updated->preserve);
        $this->assertSame($original->replace, $updated->replace);
        $this->assertSame($original->type, $updated->type);
    }
}
