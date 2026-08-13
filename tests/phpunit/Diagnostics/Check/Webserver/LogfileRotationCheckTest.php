<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\Check\Webserver\LogfileRotationCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class LogfileRotationCheckTest extends TestCase
{
    private KernelInterface&MockObject $kernel;
    private string $tempDir;
    private string $logDir;
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_log_check_' . uniqid();
        $this->logDir = $this->tempDir . '/logs';
        $this->cacheDir = $this->tempDir . '/cache';

        mkdir($this->logDir, 0777, true);
        mkdir($this->cacheDir, 0777, true);

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getLogDir')->willReturn($this->logDir);
        $this->kernel->method('getCacheDir')->willReturn($this->cacheDir);
        $this->kernel->method('getEnvironment')->willReturn('prod');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        $check = new LogfileRotationCheck($this->kernel);

        self::assertSame('log_health', $check->getId());
        self::assertSame('Log Health', $check->getLabel());
        self::assertSame('Webserver', $check->getSection());
    }

    #[Test]
    public function itReturnsWarningResultWhenLogDirectoryIsMissing(): void
    {
        $invalidKernel = $this->createMock(KernelInterface::class);
        $invalidKernel->method('getLogDir')->willReturn('/non_existent_directory_' . uniqid());
        $invalidKernel->method('getCacheDir')->willReturn($this->cacheDir);
        $invalidKernel->method('getEnvironment')->willReturn('prod');

        $check = new LogfileRotationCheck($invalidKernel);

        $result = $check->run();

        self::assertSame('log_health', $this->getProperty($result, 'id'));
        self::assertSame('Log Health', $this->getProperty($result, 'label'));
        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertSame('Missing log directory', $this->getProperty($result, 'value'));
        self::assertSame('Webserver', $this->getProperty($result, 'section'));
        self::assertSame('medium', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsOkResultWhenLogsAreHealthy(): void
    {
        file_put_contents($this->logDir . '/app.log', 'Log entry line 1' . PHP_EOL);

        $check = new LogfileRotationCheck($this->kernel, 200, 500);

        $result = $check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
        self::assertSame('0MB total', $this->getProperty($result, 'value'));
        self::assertSame('Logs healthy', $this->getMessageProperty($result));
        self::assertNull($this->getProperty($result, 'recommendation'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsWarningResultWhenLogExceedsWarningThreshold(): void
    {
        $logFile = $this->logDir . '/app.log';
        file_put_contents($logFile, 'test');

        // Create check with small thresholds (1MB warning, 2MB error)
        $check = new LogfileRotationCheck($this->kernel, 1, 2);

        // Generate a 1.5MB file
        $handle = fopen($logFile, 'wb');
        if ($handle) {
            fwrite($handle, str_repeat('a', 1024 * 1024 + 512 * 1024));
            fclose($handle);
        }

        $result = $check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertStringContainsString('exceeds 1MB', (string) $this->getMessageProperty($result));
        self::assertSame('Investigate log verbosity or rotation policy.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itReturnsErrorResultWhenLogExceedsErrorThreshold(): void
    {
        $logFile = $this->logDir . '/app.log';

        // Create check with small thresholds (1MB warning, 2MB error)
        $check = new LogfileRotationCheck($this->kernel, 1, 2);

        // Generate a 2.5MB file
        $handle = fopen($logFile, 'wb');
        if ($handle) {
            fwrite($handle, str_repeat('a', 2 * 1024 * 1024 + 512 * 1024));
            fclose($handle);
        }

        $result = $check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertStringContainsString('exceeds 2MB', (string) $this->getMessageProperty($result));
        self::assertSame('Investigate log verbosity or rotation policy.', $this->getProperty($result, 'recommendation'));
    }

    #[Test]
    public function itDetectsRapidLogGrowthFromPreviousState(): void
    {
        $logFile = $this->logDir . '/app.log';
        file_put_contents($logFile, str_repeat('a', 200000));

        // Seed state file with past data (small size 100 seconds ago)
        $stateFile = $this->cacheDir . '/log_health_state.json';
        $pastState = [
            $logFile => [
                'size' => 10,
                'time' => time() - 5, // 5 seconds ago -> rate = ~40KB/sec (> ~17KB/sec threshold)
            ],
        ];
        file_put_contents($stateFile, json_encode($pastState));

        $check = new LogfileRotationCheck($this->kernel, 200, 500);

        $result = $check->run();

        self::assertSame('warning', $this->getProperty($result, 'status'));
        self::assertStringContainsString('growing rapidly', (string) $this->getMessageProperty($result));
    }

    #[Test]
    public function itDowngradesWarningToOkInDevEnvironment(): void
    {
        $devKernel = $this->createMock(KernelInterface::class);
        $devKernel->method('getLogDir')->willReturn($this->logDir);
        $devKernel->method('getCacheDir')->willReturn($this->cacheDir);
        $devKernel->method('getEnvironment')->willReturn('dev');

        $logFile = $this->logDir . '/app.log';

        // 1.5MB file in dev mode with 1MB warning threshold
        $handle = fopen($logFile, 'wb');
        if ($handle) {
            fwrite($handle, str_repeat('a', 1024 * 1024 + 512 * 1024));
            fclose($handle);
        }

        $check = new LogfileRotationCheck($devKernel, 1, 5);

        $result = $check->run();

        self::assertSame('ok', $this->getProperty($result, 'status'));
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }

    private function getProperty(object $object, string $propertyName): mixed
    {
        $ref = new \ReflectionClass($object);

        if ($ref->hasProperty($propertyName)) {
            return $ref->getProperty($propertyName)->getValue($object);
        }

        $props = array_values($ref->getProperties());

        if ($ref->hasMethod('__construct')) {
            $params = array_values($ref->getMethod('__construct')->getParameters());
            foreach ($params as $index => $param) {
                if ($param->getName() === $propertyName && isset($props[$index])) {
                    return $props[$index]->getValue($object);
                }
            }
        }

        if ('severity' === $propertyName && isset($props[7])) {
            return $props[7]->getValue($object);
        }

        return null;
    }

    private function getMessageProperty(object $object): ?string
    {
        $ref = new \ReflectionClass($object);

        foreach (['message', 'description', 'details', 'summary', 'text'] as $propertyName) {
            if ($ref->hasProperty($propertyName)) {
                $val = $ref->getProperty($propertyName)->getValue($object);
                if (null !== $val) {
                    return (string) $val;
                }
            }
        }

        $props = array_values($ref->getProperties());

        if (isset($props[4])) {
            $val = $props[4]->getValue($object);
            if (null !== $val) {
                return (string) $val;
            }
        }

        return null;
    }
}
