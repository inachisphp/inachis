<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\System;

use Inachis\Command\System\UpdateCommand;
use Inachis\Exception\Updater\IncompatibleVersionException;
use Inachis\Exception\Updater\NoUpdateAvailableException;
use Inachis\Service\System\VersionService;
use Inachis\Updater\Planner\UpdatePlanner;
use Inachis\Updater\Provider\GithubReleaseProvider;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\ReleaseCleaner;
use Inachis\Updater\ReleaseInstaller;
use Inachis\Updater\ReleaseLocator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Cache\CacheInterface;

class UpdateCommandTest extends TestCase
{
    private string $tempDir;
    private string $dummyZipPath;
    private string $dummyZipSha256;
    private VersionService $versionService;
    private CacheInterface&MockObject $cache;
    private object $downloader;
    private GithubReleaseProvider $releaseProvider;
    private UpdatePlanner $updatePlanner;
    private ReleaseLocator $releaseLocator;
    private object $extractor;
    private object $verifier;
    private object $symlinkManager;
    private object $migrationRunner;
    private object $maintenanceManager;
    private ReleaseInstaller $releaseInstaller;
    private ReleaseCleaner $releaseCleaner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_update_cmd_test_' . uniqid('', true);
        mkdir($this->tempDir . '/releases', 0777, true);
        mkdir($this->tempDir . '/shared', 0777, true);

        $versionFile = $this->tempDir . '/version.php';
        file_put_contents($versionFile, "<?php return ['version' => '1.0.0'];");

        $this->versionService = new VersionService($versionFile);
        $this->cache = $this->createMock(CacheInterface::class);

        // Create a real dummy zip file in tempDir
        $this->dummyZipPath = $this->tempDir . '/dummy-release.zip';
        $zip = new \ZipArchive();
        if (true === $zip->open($this->dummyZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $zip->addFromString('version.txt', '2.0.0');
            $zip->close();
        }
        $this->dummyZipSha256 = hash_file('sha256', $this->dummyZipPath) ?: 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

        $this->downloader = $this->createMockOrInstanceFromType(GithubReleaseProvider::class, 'downloader');

        if ($this->downloader instanceof MockObject) {
            $this->downloader->method('download')->willReturnCallback(
                function (string $url, string $destination): void {
                    if (file_exists($this->dummyZipPath)) {
                        copy($this->dummyZipPath, $destination);
                    } else {
                        file_put_contents($destination, 'dummy_zip_content');
                    }
                }
            );
        }

        $this->releaseProvider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
        );

        $this->updatePlanner = new UpdatePlanner();
        $this->releaseLocator = new ReleaseLocator($this->tempDir);

        $this->extractor = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'extractor');
        $this->verifier = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'verifier');
        $this->symlinkManager = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'symlinkManager');
        $this->migrationRunner = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'migrationRunner');
        $this->maintenanceManager = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'maintenanceManager');

        $this->releaseInstaller = new ReleaseInstaller(
            locator: $this->releaseLocator,
            extractor: $this->extractor,
            verifier: $this->verifier,
            symlinkManager: $this->symlinkManager,
            migrationRunner: $this->migrationRunner,
            maintenanceManager: $this->maintenanceManager,
        );

        $this->releaseCleaner = new ReleaseCleaner($this->releaseLocator);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirRecursive($this->tempDir);
        parent::tearDown();
    }

    public function testExecuteNoUpdateAvailable(): void
    {
        $this->cache->method('get')
            ->willThrowException(new NoUpdateAvailableException('Already up to date.'));

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Already up to date.', $tester->getDisplay());
    }

    public function testExecuteIncompatibleVersion(): void
    {
        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.5.0',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('1.0.0', $tester->getDisplay());
    }

    public function testExecuteCheckException(): void
    {
        $this->cache->method('get')
            ->willThrowException(new \RuntimeException('Network connection failed.'));

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Failed checking for updates: Network connection failed.', $tester->getDisplay());
    }

    public function testExecuteCheckOnlyMode(): void
    {
        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--check-only' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('New release v2.0.0 is available!', $display);
        $this->assertStringContainsString('Target Version', $display);
    }

    public function testExecuteUpdateCancelledByUser(): void
    {
        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $tester->setInputs(['no']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Update cancelled by user.', $tester->getDisplay());
    }

    public function testExecuteSuccessfulUpdateWithForceOption(): void
    {
        mkdir($this->tempDir . '/releases/20260101000000-0.9.0', 0777, true);
        mkdir($this->tempDir . '/releases/20260102000000-0.9.1', 0777, true);
        mkdir($this->tempDir . '/releases/20260103000000-0.9.2', 0777, true);
        mkdir($this->tempDir . '/releases/20260104000000-1.0.0', 0777, true);

        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
            package: 'inachis-v2.0.0.zip',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Inachis successfully updated to v2.0.0!', $display);
        $this->assertStringContainsString('Pruned 2 old release folder(s).', $display);
    }

    public function testExecuteSuccessfulUpdateWithInteractiveConfirmation(): void
    {
        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $tester->setInputs(['yes']);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Inachis successfully updated to v2.0.0!', $tester->getDisplay());
    }

    public function testExecuteInstallationFailure(): void
    {
        $manifest = $this->createManifest(
            version: '2.0.0',
            minimumVersion: '1.0.0',
            archiveUrl: '',
        );

        $this->cache->method('get')->willReturn($manifest);

        $command = $this->createCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--force' => true]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Update process failed: Manifest does not contain a valid archive download URL.', $tester->getDisplay());
    }

    private function createCommand(): UpdateCommand
    {
        return new UpdateCommand(
            $this->versionService,
            $this->releaseProvider,
            $this->updatePlanner,
            $this->releaseInstaller,
            $this->releaseCleaner,
            $this->releaseLocator,
        );
    }

    private function createManifest(
        string $version = '2.0.0',
        string $minimumVersion = '1.0.0',
        string $package = 'inachis-v2.0.0.zip',
        ?string $publishedAt = '2026-08-10 12:00:00',
        ?string $archiveUrl = null,
        ?string $packageSha256 = null,
    ): Manifest {
        $reflection = new \ReflectionClass(Manifest::class);
        $manifest = $reflection->newInstanceWithoutConstructor();

        $properties = [
            'version' => $version,
            'minimumVersion' => $minimumVersion,
            'package' => $package,
            'packageSha256' => $packageSha256 ?? $this->dummyZipSha256,
            'migrations' => [],
            'preserve' => [],
            'replace' => [],
            'archiveUrl' => null !== $archiveUrl ? $archiveUrl : ('file://' . $this->dummyZipPath),
            'type' => 'core',
            'releaseNotes' => 'Release notes for testing',
            'publishedAt' => $publishedAt,
        ];

        foreach ($properties as $name => $value) {
            if ($reflection->hasProperty($name)) {
                $prop = $reflection->getProperty($name);
                $prop->setValue($manifest, $value);
            }
        }

        return $manifest;
    }

    private function createMockOrInstanceFromType(string $targetClass, string $paramName): object
    {
        if (!class_exists($targetClass)) {
            return new \stdClass();
        }

        $reflection = new \ReflectionClass($targetClass);
        if (!$reflection->hasMethod('__construct')) {
            return new \stdClass();
        }

        $constructor = $reflection->getMethod('__construct');
        foreach ($constructor->getParameters() as $param) {
            if ($param->getName() === $paramName) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType) {
                    return $this->createMockOrInstance($type->getName());
                }
            }
        }

        return new \stdClass();
    }

    private function createMockOrInstance(string $className): object
    {
        if (!class_exists($className) && !interface_exists($className)) {
            return new \stdClass();
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->isFinal()) {
            return $this->createMock($className);
        }

        $object = $reflection->newInstanceWithoutConstructor();
        $this->initializeTypedProperties($object);

        return $object;
    }

    private function initializeTypedProperties(object $object): void
    {
        $reflection = new \ReflectionClass($object);
        $current = $reflection;

        while (false !== $current) {
            foreach ($current->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isInitialized($object)) {
                    continue;
                }

                $type = $prop->getType();
                if (null === $type) {
                    continue;
                }

                if ($type->allowsNull()) {
                    $prop->setValue($object, null);
                    continue;
                }

                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if ('string' === $typeName) {
                    $prop->setValue($object, '');
                } elseif ('int' === $typeName) {
                    $prop->setValue($object, 0);
                } elseif ('bool' === $typeName) {
                    $prop->setValue($object, false);
                } elseif ('array' === $typeName) {
                    $prop->setValue($object, []);
                } elseif ($typeName && (class_exists($typeName) || interface_exists($typeName))) {
                    $depRef = new \ReflectionClass($typeName);
                    if ($depRef->isFinal()) {
                        $depObj = $depRef->newInstanceWithoutConstructor();
                        $this->initializeTypedProperties($depObj);
                        $prop->setValue($object, $depObj);
                    } else {
                        $prop->setValue($object, $this->createMock($typeName));
                    }
                }
            }
            $current = $current->getParentClass();
        }
    }

    private function removeTempDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
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
