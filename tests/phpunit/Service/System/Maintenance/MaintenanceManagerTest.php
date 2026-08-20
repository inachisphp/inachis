<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Maintenance;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class MaintenanceManagerTest extends TestCase
{
    private string $tempDir;
    private Environment&MockObject $twig;
    private MaintenanceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/inachis_maint_test_'.uniqid('', true);
        mkdir($this->tempDir.'/var', 0777, true);
        mkdir($this->tempDir.'/public', 0777, true);

        $this->twig = $this->createMock(Environment::class);
        $this->manager = new MaintenanceManager($this->tempDir, $this->twig);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirRecursive($this->tempDir);
        parent::tearDown();
    }

    public function testIsEnabledReturnsFalseWhenLockFileDoesNotExist(): void
    {
        $this->assertFalse($this->manager->isEnabled());
    }

    public function testIsEnabledReturnsTrueWhenLockFileExists(): void
    {
        touch($this->tempDir.'/var/maintenance.lock');

        $this->assertTrue($this->manager->isEnabled());
    }

    public function testEnableCreatesLockFileAndGeneratesStaticPage(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('web/maintenance_template.html.twig', $this->manager->getConfig())
            ->willReturn('<html>Maintenance</html>');

        $this->manager->enable();

        $this->assertFileExists($this->tempDir.'/var/maintenance.lock');
        $this->assertFileExists($this->tempDir.'/public/maintenance.html');
        $this->assertSame('<html>Maintenance</html>', file_get_contents($this->tempDir.'/public/maintenance.html'));
    }

    public function testDisableRemovesLockFileAndMaintenanceHtml(): void
    {
        touch($this->tempDir.'/var/maintenance.lock');
        file_put_contents($this->tempDir.'/public/maintenance.html', '<html>Maintenance</html>');

        $this->manager->disable();

        $this->assertFileDoesNotExist($this->tempDir.'/var/maintenance.lock');
        $this->assertFileDoesNotExist($this->tempDir.'/public/maintenance.html');
    }

    public function testGetConfigReturnsDefaultConfigWhenFileDoesNotExist(): void
    {
        $expected = [
            'message' => 'Our site is currently undergoing scheduled maintenance.',
            'estimated_downtime' => '1 hour',
            'allowed_ips' => [],
            'retry_after' => 3600,
        ];

        $this->assertSame($expected, $this->manager->getConfig());
    }

    public function testGetConfigReturnsParsedJsonWhenFileExists(): void
    {
        $customConfig = [
            'message' => 'Custom maintenance message',
            'estimated_downtime' => '2 hours',
            'allowed_ips' => ['127.0.0.1'],
            'retry_after' => 7200,
        ];

        file_put_contents($this->tempDir.'/var/maintenance.json', json_encode($customConfig));

        $this->assertSame($customConfig, $this->manager->getConfig());
    }

    public function testGetConfigReturnsEmptyArrayWhenJsonIsInvalid(): void
    {
        file_put_contents($this->tempDir.'/var/maintenance.json', 'INVALID_JSON{');

        $this->assertSame([], $this->manager->getConfig());
    }

    public function testSaveConfigWritesConfigFileAtomically(): void
    {
        $config = [
            'message' => 'New Maintenance Message',
            'estimated_downtime' => '30 mins',
            'allowed_ips' => ['192.168.1.1'],
            'retry_after' => 1800,
        ];

        $this->manager->saveConfig($config);

        $this->assertFileExists($this->tempDir.'/var/maintenance.json');
        $decoded = json_decode(file_get_contents($this->tempDir.'/var/maintenance.json'), true);
        $this->assertSame($config, $decoded);
    }

    public function testSaveConfigThrowsRuntimeExceptionOnJsonEncodeFailure(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode maintenance config to JSON.');

        $invalidConfig = ['invalid_float' => NAN];

        $this->manager->saveConfig($invalidConfig);
    }

    public function testGenerateStaticPageRendersTemplateAndWritesHtmlFile(): void
    {
        $config = ['message' => 'Test'];

        $this->twig->expects($this->once())
            ->method('render')
            ->with('web/maintenance_template.html.twig', $config)
            ->willReturn('<h1>Site Maintenance</h1>');

        $this->manager->generateStaticPage($config);

        $this->assertFileExists($this->tempDir.'/public/maintenance.html');
        $this->assertSame('<h1>Site Maintenance</h1>', file_get_contents($this->tempDir.'/public/maintenance.html'));
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
