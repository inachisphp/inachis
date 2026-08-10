<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System;

use Inachis\Service\System\VersionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionServiceTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_version_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirRecursive($this->tempDir);
        parent::tearDown();
    }

    public function testDefaultsWhenVersionFileDoesNotExist(): void
    {
        $service = new VersionService($this->tempDir . '/non_existent.php');

        $this->assertSame('dev', $service->getVersion());
        $this->assertSame('unknown', $service->getCommit());
        $this->assertSame('', $service->getBuildDate());
        $this->assertSame([
            'version' => 'dev',
            'commit' => 'unknown',
            'build_date' => '',
        ], $service->getAll());
    }

    public function testLoadsVersionInformationFromFile(): void
    {
        $versionFile = $this->tempDir . '/version.php';
        file_put_contents($versionFile, <<<'PHP'
<?php
return [
    'version' => '1.2.3',
    'commit' => 'a1b2c3d4e5f6',
    'build_date' => '2026-08-10 12:00:00',
];
PHP
        );

        $service = new VersionService($versionFile);

        $this->assertSame('1.2.3', $service->getVersion());
        $this->assertSame('a1b2c3d4e5f6', $service->getCommit());
        $this->assertSame('2026-08-10 12:00:00', $service->getBuildDate());
        $this->assertSame([
            'version' => '1.2.3',
            'commit' => 'a1b2c3d4e5f6',
            'build_date' => '2026-08-10 12:00:00',
        ], $service->getAll());
    }

    public function testFallbackValuesWhenKeysMissingInVersionFile(): void
    {
        $versionFile = $this->tempDir . '/partial_version.php';
        file_put_contents($versionFile, <<<'PHP'
<?php
return [
    'version' => '2.0.0',
];
PHP
        );

        $service = new VersionService($versionFile);

        $this->assertSame('2.0.0', $service->getVersion());
        $this->assertSame('unknown', $service->getCommit());
        $this->assertSame('', $service->getBuildDate());
    }

    public function testSatisfiesReturnsFalseForDevVersion(): void
    {
        $service = new VersionService($this->tempDir . '/non_existent.php');

        $this->assertFalse($service->satisfies('^1.0.0'));
        $this->assertFalse($service->satisfies('>= 1.0.0'));
        $this->assertFalse($service->satisfies('1.0.0'));
    }

    /**
     * @dataProvider caretConstraintProvider
     */
    #[DataProvider('caretConstraintProvider')]
    public function testSatisfiesWithCaretConstraint(string $version, string $constraint, bool $expected): void
    {
        $versionFile = $this->createVersionFile($version);
        $service = new VersionService($versionFile);

        $this->assertSame($expected, $service->satisfies($constraint));
    }

    /**
     * @return list<array{string, string, bool}>
     */
    public static function caretConstraintProvider(): array
    {
        return [
            ['1.2.3', '^1.2.0', true],
            ['1.5.0', '^1.2.0', true],
            ['2.0.0', '^1.2.0', false],
            ['1.1.9', '^1.2.0', false],
            ['0.2.1', '^0.2.0', true],
            ['1.0.0', '^0.2.0', false],
        ];
    }

    /**
     * @dataProvider tildeConstraintProvider
     */
    #[DataProvider('tildeConstraintProvider')]
    public function testSatisfiesWithTildeConstraint(string $version, string $constraint, bool $expected): void
    {
        $versionFile = $this->createVersionFile($version);
        $service = new VersionService($versionFile);

        $this->assertSame($expected, $service->satisfies($constraint));
    }

    /**
     * @return list<array{string, string, bool}>
     */
    public static function tildeConstraintProvider(): array
    {
        return [
            ['1.2.3', '~1.2.0', true],
            ['1.2.9', '~1.2.0', true],
            ['1.3.0', '~1.2.0', false],
            ['1.1.9', '~1.2.0', false],
        ];
    }

    /**
     * @dataProvider comparisonOperatorProvider
     */
    #[DataProvider('comparisonOperatorProvider')]
    public function testSatisfiesWithComparisonOperators(string $version, string $constraint, bool $expected): void
    {
        $versionFile = $this->createVersionFile($version);
        $service = new VersionService($versionFile);

        $this->assertSame($expected, $service->satisfies($constraint));
    }

    /**
     * @return list<array{string, string, bool}>
     */
    public static function comparisonOperatorProvider(): array
    {
        return [
            ['1.2.0', '>= 1.0.0', true],
            ['1.0.0', '>= 1.0.0', true],
            ['0.9.0', '>= 1.0.0', false],
            ['1.2.0', '> 1.0.0', true],
            ['1.0.0', '> 1.0.0', false],
            ['1.2.0', '<= 1.2.0', true],
            ['1.2.1', '<= 1.2.0', false],
            ['1.0.0', '< 1.2.0', true],
            ['1.2.0', '< 1.2.0', false],
            ['1.2.0', '!= 1.0.0', true],
            ['1.0.0', '!= 1.0.0', false],
            ['1.2.0', '= 1.2.0', true],
            ['1.2.1', '= 1.2.0', false],
        ];
    }

    /**
     * @dataProvider exactVersionProvider
     */
    #[DataProvider('exactVersionProvider')]
    public function testSatisfiesWithExactVersion(string $version, string $constraint, bool $expected): void
    {
        $versionFile = $this->createVersionFile($version);
        $service = new VersionService($versionFile);

        $this->assertSame($expected, $service->satisfies($constraint));
    }

    /**
     * @return list<array{string, string, bool}>
     */
    public static function exactVersionProvider(): array
    {
        return [
            ['1.2.0', '1.2.0', true],
            ['1.2.1', '1.2.0', false],
        ];
    }

    private function createVersionFile(string $version): string
    {
        $file = $this->tempDir . '/version_' . md5($version) . '.php';
        file_put_contents($file, sprintf('<?php return ["version" => "%s"];', $version));

        return $file;
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
