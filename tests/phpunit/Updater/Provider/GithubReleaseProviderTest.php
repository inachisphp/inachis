<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater\Provider;

use Inachis\Updater\Downloader\Downloader;
use Inachis\Updater\Provider\GithubReleaseProvider;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\Release\ManifestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GithubReleaseProviderTest extends TestCase
{
    private Downloader $downloader;
    private CacheInterface $cache;
    private ManifestFactory $manifestFactory;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate real final objects directly
        $this->downloader = new Downloader();
        $this->cache = $this->createMock(CacheInterface::class);
        $this->manifestFactory = new ManifestFactory();

        $this->tempDir = sys_get_temp_dir().'/inachis_provider_test_'.uniqid('', true);
        mkdir($this->tempDir, 0777, true);

        // Configure mock cache to execute the callback directly
        $this->cache->method('get')->willReturnCallback(function (string $key, callable $callback) {
            $item = $this->createStub(ItemInterface::class);

            return $callback($item);
        });

        // Register custom stream wrapper to intercept https:// HTTP requests
        if (in_array('https', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('https');
        }
        stream_wrapper_register('https', MockHttpsStreamWrapper::class);
        MockHttpsStreamWrapper::$responses = [];
    }

    protected function tearDown(): void
    {
        // Restore default https stream wrapper
        if (in_array('https', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('https');
        }
        stream_wrapper_restore('https');

        $this->removeTempDirRecursive($this->tempDir);

        parent::tearDown();
    }

    public function testLatestFetchesAndReturnsManifestSuccessfully(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';
        $manifestUrl = 'https://api.github.com/assets/manifest.json';

        MockHttpsStreamWrapper::$responses[$releaseUrl] = json_encode([
            'body' => 'v2.0.0 release notes',
            'published_at' => '2026-08-10T10:00:00Z',
            'assets' => [
                [
                    'name' => 'manifest.json',
                    'browser_download_url' => $manifestUrl,
                ],
                [
                    'name' => 'inachis-2.0.0.zip',
                    'browser_download_url' => 'https://github.com/inachisphp/inachis/releases/download/v2.0.0/inachis-2.0.0.zip',
                ],
            ],
        ]);

        MockHttpsStreamWrapper::$responses[$manifestUrl] = json_encode([
            'version' => '2.0.0',
            'minimumVersion' => '1.0.0',
            'package' => 'inachis-2.0.0.zip',
            'packageSha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'migrations' => [],
            'preserve' => [],
            'replace' => [],
        ]);

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $manifest = $provider->latest();

        $this->assertInstanceOf(Manifest::class, $manifest);
        $this->assertSame('2.0.0', $manifest->version);
        $this->assertSame('inachis-2.0.0.zip', $manifest->package);
        $this->assertSame('https://github.com/inachisphp/inachis/releases/download/v2.0.0/inachis-2.0.0.zip', $manifest->archiveUrl);
        $this->assertSame('v2.0.0 release notes', $manifest->releaseNotes);
        $this->assertSame('2026-08-10T10:00:00Z', $manifest->publishedAt);
    }

    public function testVersionFetchesAndReturnsManifestForSpecificVersion(): void
    {
        $version = 'v2.1.0';
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/tags/v2.1.0';
        $manifestUrl = 'https://api.github.com/assets/manifest.json';

        MockHttpsStreamWrapper::$responses[$releaseUrl] = json_encode([
            'body' => 'v2.1.0 release notes',
            'published_at' => '2026-08-10T12:00:00Z',
            'assets' => [
                [
                    'name' => 'manifest.json',
                    'browser_download_url' => $manifestUrl,
                ],
                [
                    'name' => 'inachis-2.1.0.zip',
                    'browser_download_url' => 'https://github.com/inachisphp/inachis/releases/download/v2.1.0/inachis-2.1.0.zip',
                ],
            ],
        ]);

        MockHttpsStreamWrapper::$responses[$manifestUrl] = json_encode([
            'version' => '2.1.0',
            'minimumVersion' => '2.0.0',
            'package' => 'inachis-2.1.0.zip',
            'packageSha256' => 'abc123sha256',
        ]);

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $manifest = $provider->version($version);

        $this->assertSame('2.1.0', $manifest->version);
        $this->assertSame('https://github.com/inachisphp/inachis/releases/download/v2.1.0/inachis-2.1.0.zip', $manifest->archiveUrl);
    }

    public function testDownloadStreamsFileToDestinationSuccessfully(): void
    {
        $archiveUrl = 'https://example.com/download.zip';
        MockHttpsStreamWrapper::$responses[$archiveUrl] = 'MOCK_ZIP_CONTENT';

        $manifest = new Manifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
            package: 'inachis-2.0.0.zip',
            packageSha256: 'hash',
            archiveUrl: $archiveUrl,
        );

        $destination = $this->tempDir.'/release.zip';

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $provider->download($manifest, $destination);

        $this->assertFileExists($destination);
        $this->assertSame('MOCK_ZIP_CONTENT', file_get_contents($destination));
    }

    public function testDownloadThrowsExceptionWhenArchiveUrlIsEmpty(): void
    {
        $manifest = new Manifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
            package: 'inachis-2.0.0.zip',
            packageSha256: 'hash',
            archiveUrl: null,
        );

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Manifest does not contain a valid archive download URL.');

        $provider->download($manifest, $this->tempDir.'/release.zip');
    }

    public function testFetchReleaseThrowsExceptionWhenAssetsAreMissing(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';

        MockHttpsStreamWrapper::$responses[$releaseUrl] = json_encode([
            'body' => 'No assets here',
        ]);

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GitHub release does not contain any assets.');

        $provider->latest();
    }

    public function testFetchReleaseThrowsExceptionWhenNoManifestJsonAssetFound(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';

        MockHttpsStreamWrapper::$responses[$releaseUrl] = json_encode([
            'assets' => [
                [
                    'name' => 'inachis-2.0.0.zip',
                    'browser_download_url' => 'https://example.com/inachis-2.0.0.zip',
                ],
            ],
        ]);

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No release manifest JSON asset found.');

        $provider->latest();
    }

    public function testFetchReleaseThrowsExceptionWhenPackageListedInManifestIsMissingFromAssets(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';
        $manifestUrl = 'https://api.github.com/assets/manifest.json';

        MockHttpsStreamWrapper::$responses[$releaseUrl] = json_encode([
            'assets' => [
                [
                    'name' => 'manifest.json',
                    'browser_download_url' => $manifestUrl,
                ],
            ],
        ]);

        MockHttpsStreamWrapper::$responses[$manifestUrl] = json_encode([
            'version' => '2.0.0',
            'minimumVersion' => '1.0.0',
            'package' => 'missing-package.zip',
            'packageSha256' => 'hash',
        ]);

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Release package "missing-package.zip" listed in manifest was not found in release assets.');

        $provider->latest();
    }

    public function testRequestJsonThrowsExceptionOnFetchFailure(): void
    {
        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to fetch data from');

        $provider->latest();
    }

    public function testRequestJsonThrowsExceptionOnInvalidJson(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';
        MockHttpsStreamWrapper::$responses[$releaseUrl] = 'INVALID JSON{{{';

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON received from');

        $provider->latest();
    }

    public function testRequestJsonThrowsExceptionOnNonArrayJson(): void
    {
        $releaseUrl = 'https://api.github.com/repos/inachisphp/inachis/releases/latest';
        MockHttpsStreamWrapper::$responses[$releaseUrl] = '"just a string"';

        $provider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
            manifestFactory: $this->manifestFactory,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unexpected JSON structure from');

        $provider->latest();
    }

    private function removeTempDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeTempDirRecursive($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}

/**
 * Mock stream wrapper used to intercept stream calls to https:// during tests.
 */
class MockHttpsStreamWrapper
{
    /** @var array<string, string> */
    public static array $responses = [];

    /** @var resource|null */
    public $context;

    private int $position = 0;
    private string $content = '';

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        if (isset(self::$responses[$path])) {
            $this->content = self::$responses[$path];
            $this->position = 0;

            return true;
        }

        return false;
    }

    public function stream_read(int $count): string
    {
        $ret = substr($this->content, $this->position, $count);
        $this->position += strlen($ret);

        return $ret;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function stream_close(): void
    {
    }

    /**
     * @return array<int|string, int>
     */
    public function stream_stat(): array
    {
        return [];
    }
}
