<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Updater;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Service\System\Maintenance\MaintenanceManager;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Inachis\Updater\Migration\MigrationRunner;
use Inachis\Updater\ReleaseExtractor;
use Inachis\Updater\ReleaseInstance;
use Inachis\Updater\ReleaseInstaller;
use Inachis\Updater\ReleaseLocator;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\SymlinkManager;
use Inachis\Updater\Verify\ReleaseVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

final class ReleaseInstallerTest extends InachisControllerTestCase
{
    private string $installRoot;

    private ReleaseLocator $locator;

    private ReleaseExtractor $extractor;

    private ReleaseVerifier $verifier;

    private SymlinkManager $symlinkManager;

    private MigrationRunner $migrationRunner;

    /** @var MaintenanceManager&MockObject */
    private MaintenanceManager $maintenanceManager;

    private ReleaseInstaller $installer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installRoot = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'inachis-installer-test-'
            .bin2hex(random_bytes(8));

        mkdir($this->installRoot, 0775, true);
        mkdir($this->installRoot.'/var', 0775, true);
        mkdir($this->installRoot.'/public', 0775, true);

        $this->locator = new ReleaseLocator($this->installRoot);
        $this->extractor = new ReleaseExtractor();
        $this->verifier = new ReleaseVerifier();
        $this->symlinkManager = new SymlinkManager();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->migrationRunner = new MigrationRunner($entityManager);

        $twig = $this->createStub(Environment::class);

        $this->maintenanceManager = $this->createMock(
            MaintenanceManager::class,
        );

        $this->installer = new ReleaseInstaller(
            $this->locator,
            $this->extractor,
            $this->verifier,
            $this->symlinkManager,
            $this->migrationRunner,
            $this->maintenanceManager,
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->installRoot);

        parent::tearDown();
    }

    public function testInstallSuccessfully(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "test";',
        ]);

        $manifest = $this->createManifest($archive);

        $this->maintenanceManager
            ->expects($this->once())
            ->method('enable');

        $this->maintenanceManager
            ->expects($this->once())
            ->method('disable');

        $release = $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertInstanceOf(ReleaseInstance::class, $release);
        self::assertSame('1.2.3', $release->version);
        self::assertDirectoryExists($release->path);
        self::assertFileExists($release->path.'/index.php');
        self::assertSame(
            '<?php echo "test";',
            file_get_contents($release->path.'/index.php'),
        );

        self::assertTrue(
            is_link($this->locator->currentLink()),
        );

        self::assertSame(
            $release->path,
            readlink($this->locator->currentLink()),
        );
    }

    public function testInstallStopsWhenVerificationFails(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "test";',
        ]);

        $manifest = new Manifest(
            version: '1.2.3',
            minimumVersion: '1.0.0',
            package: basename($archive),
            packageSha256: str_repeat('0', 64),
        );

        $this->maintenanceManager
            ->expects($this->never())
            ->method('enable');

        $this->maintenanceManager
            ->expects($this->never())
            ->method('disable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Release checksum mismatch');

        $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertDirectoryDoesNotExist(
            $this->locator->releasesDirectory(),
        );
    }

    public function testInstallStopsWhenArchiveDoesNotExist(): void
    {
        $archive = $this->installRoot.'/missing.zip';

        $manifest = new Manifest(
            version: '1.2.3',
            minimumVersion: '1.0.0',
            package: 'missing.zip',
            packageSha256: str_repeat('0', 64),
        );

        $this->maintenanceManager
            ->expects($this->never())
            ->method('enable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->installer->install(
            $manifest,
            $archive,
        );
    }

    public function testInstallStopsWhenExtractionFails(): void
    {
        /*
         * The archive passes checksum verification but isn't a valid ZIP.
         */
        $archive = $this->installRoot.'/invalid.zip';

        file_put_contents(
            $archive,
            'This is not a ZIP archive.',
        );

        $manifest = $this->createManifest($archive);

        $this->maintenanceManager
            ->expects($this->never())
            ->method('enable');

        $this->maintenanceManager
            ->expects($this->never())
            ->method('disable');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to open release archive');

        $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertDirectoryDoesNotExist(
            $this->locator->releasesDirectory(),
        );
    }

    public function testInstallPassesSharedDirectoryMappings(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "test";',
            'var/data/.gitkeep' => '',
        ]);

        $manifest = $this->createManifest($archive);

        $sharedDirectory = $this->locator->sharedDirectory().'/data';

        mkdir($sharedDirectory, 0775, true);

        $mappings = [
            'var/data' => $sharedDirectory,
        ];

        $this->maintenanceManager
            ->expects($this->once())
            ->method('enable');

        $this->maintenanceManager
            ->expects($this->once())
            ->method('disable');

        $release = $this->installer->install(
            $manifest,
            $archive,
            $mappings,
        );

        self::assertTrue(
            is_link($release->path.'/var/data'),
        );

        self::assertSame(
            $sharedDirectory,
            readlink($release->path.'/var/data'),
        );
    }

    public function testInstallUsesEmptyMappingsByDefault(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "test";',
        ]);

        $manifest = $this->createManifest($archive);

        $this->maintenanceManager
            ->expects($this->once())
            ->method('enable');

        $this->maintenanceManager
            ->expects($this->once())
            ->method('disable');

        $release = $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertFileExists(
            $release->path.'/index.php',
        );

        self::assertDirectoryDoesNotExist(
            $release->path.'/var',
        );
    }

    public function testMaintenanceModeIsDisabledAfterSuccessfulInstallation(): void
    {
        $archive = $this->createArchive([
            'index.php' => '<?php echo "test";',
        ]);

        $manifest = $this->createManifest($archive);

        $invocations = [];

        $this->maintenanceManager
            ->expects($this->once())
            ->method('enable')
            ->willReturnCallback(
                static function () use (&$invocations): void {
                    $invocations[] = 'enable';
                },
            );

        $this->maintenanceManager
            ->expects($this->once())
            ->method('disable')
            ->willReturnCallback(
                static function () use (&$invocations): void {
                    $invocations[] = 'disable';
                },
            );

        $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertSame(
            ['enable', 'disable'],
            $invocations,
        );
    }

    public function testSuccessfulInstallationReturnsCreatedRelease(): void
    {
        $archive = $this->createArchive([
            'README.md' => 'Inachis release',
        ]);

        $manifest = new Manifest(
            version: '2.0.0',
            minimumVersion: '1.5.0',
            package: basename($archive),
            packageSha256: hash_file('sha256', $archive),
        );

        $this->maintenanceManager
            ->method('enable');

        $this->maintenanceManager
            ->method('disable');

        $release = $this->installer->install(
            $manifest,
            $archive,
        );

        self::assertSame(
            '2.0.0',
            $release->version,
        );

        self::assertStringContainsString(
            '2.0.0',
            $release->identifier,
        );

        self::assertDirectoryExists(
            $release->path,
        );

        self::assertFileExists(
            $release->path.'/README.md',
        );
    }

    private function createManifest(string $archive): Manifest
    {
        $checksum = hash_file('sha256', $archive);

        self::assertIsString($checksum);

        return new Manifest(
            version: '1.2.3',
            minimumVersion: '1.0.0',
            package: basename($archive),
            packageSha256: $checksum,
        );
    }

    /**
     * @param array<string, string> $files
     */
    private function createArchive(array $files): string
    {
        $archive = $this->installRoot
            .DIRECTORY_SEPARATOR
            .'package-'
            .bin2hex(random_bytes(8))
            .'.zip';

        $zip = new \ZipArchive();

        $result = $zip->open(
            $archive,
            \ZipArchive::CREATE | \ZipArchive::OVERWRITE,
        );

        self::assertTrue($result);

        foreach ($files as $filename => $contents) {
            $zip->addFromString(
                $filename,
                $contents,
            );
        }

        $zip->close();

        self::assertFileExists($archive);

        return $archive;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (
            array_diff(
                scandir($directory) ?: [],
                ['.', '..'],
            ) as $file
        ) {
            $path = $directory
                .DIRECTORY_SEPARATOR
                .$file;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
