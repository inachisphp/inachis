<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Performance;

use Inachis\Diagnostics\Check\Performance\IoPerformanceCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class IoPerformanceCheckTest extends TestCase
{
    private KernelInterface&MockObject $kernel;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_io_test_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }

        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->method('getCacheDir')->willReturn($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempDir . '/io_test.tmp')) {
            @unlink($this->tempDir . '/io_test.tmp');
        }

        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    #[Test]
    public function itReturnsCorrectMetadata(): void
    {
        $check = new IoPerformanceCheck($this->kernel);

        self::assertSame('io_performance', $check->getId());
        self::assertSame('Disk I/O Performance', $check->getLabel());
        self::assertSame('Performance', $check->getSection());
    }

    #[Test]
    public function itRunsCheckSuccessfully(): void
    {
        $check = new IoPerformanceCheck($this->kernel);

        $result = $check->run();

        self::assertSame('io_performance', $this->getProperty($result, 'id'));
        self::assertSame('Disk I/O Performance', $this->getProperty($result, 'label'));
        self::assertContains($this->getProperty($result, 'status'), ['ok', 'warning', 'error']);
        self::assertStringEndsWith('MB/s', (string) $this->getProperty($result, 'value'));
        self::assertStringContainsString('Write:', (string) $this->getMessageProperty($result));
        self::assertStringContainsString('Read:', (string) $this->getMessageProperty($result));
        self::assertSame('Performance', $this->getProperty($result, 'section'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
    }

    #[Test]
    public function itReturnsErrorResultWhenCacheDirIsNotWritable(): void
    {
        $invalidKernel = $this->createMock(KernelInterface::class);
        $invalidKernel->method('getCacheDir')->willReturn('/non_existent_directory_' . uniqid());

        $check = new IoPerformanceCheck($invalidKernel);

        $result = $check->run();

        self::assertSame('error', $this->getProperty($result, 'status'));
        self::assertSame('I/O test failed', $this->getProperty($result, 'value'));
        self::assertStringContainsString('Could not write to', (string) $this->getMessageProperty($result));
        self::assertSame('Verify filesystem permissions and disk health.', $this->getProperty($result, 'recommendation'));
        self::assertSame('high', $this->getProperty($result, 'severity'));
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
