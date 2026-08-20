<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Theme;

use Inachis\Model\System\Theme;
use Inachis\Repository\System\SettingRepository;
use Inachis\Service\Theme\ThemeManager;
use Inachis\Service\Theme\ThemeScanner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class ThemeManagerTest extends TestCase
{
    private SettingRepository&MockObject $settingRepository;
    private CacheItemPoolInterface&MockObject $cachePool;
    private CacheItemInterface&MockObject $cacheItem;
    private string $projectDir = '/tmp/inachis_test_project';

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingRepository = $this->createMock(SettingRepository::class);
        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
    }

    public function testGetActiveThemeIdentifierReturnsSettingValue(): void
    {
        $this->settingRepository->expects($this->once())
            ->method('getValue')
            ->with(ThemeManager::SETTING_ACTIVE_THEME)
            ->willReturn('custom-theme');

        $manager = $this->createThemeManager();

        $this->assertSame('custom-theme', $manager->getActiveThemeIdentifier());
    }

    public function testGetActiveThemeIdentifierDefaultsToDefaultWhenNull(): void
    {
        $this->settingRepository->expects($this->once())
            ->method('getValue')
            ->with(ThemeManager::SETTING_ACTIVE_THEME)
            ->willReturn(null);

        $manager = $this->createThemeManager();

        $this->assertSame('default', $manager->getActiveThemeIdentifier());
    }

    public function testSetActiveThemeSetsSettingAndClearsCache(): void
    {
        $this->settingRepository->expects($this->once())
            ->method('setValue')
            ->with(ThemeManager::SETTING_ACTIVE_THEME, 'new-theme');

        $this->cachePool->expects($this->exactly(2))
            ->method('deleteItem')
            ->willReturnMap([
                ['theme.active.dto', true],
                ['theme.twig.paths', true],
            ]);

        $manager = $this->createThemeManager();
        $manager->setActiveTheme('new-theme');
    }

    public function testGetActiveThemeReturnsCachedThemeOnCacheHit(): void
    {
        $cachedTheme = $this->createTheme('cached-theme', isCompatible: true);

        $this->cacheItem->method('isHit')->willReturn(true);
        $this->cacheItem->method('get')->willReturn($cachedTheme);
        $this->cachePool->method('getItem')->willReturnMap([
            ['theme.active.dto', $this->cacheItem],
        ]);

        $themeScanner = $this->createThemeScanner();
        $manager = $this->createThemeManager($themeScanner);

        $result = $manager->getActiveTheme();

        $this->assertSame($cachedTheme, $result);
    }

    public function testGetActiveThemeScansAndCachesThemeWhenNotCached(): void
    {
        $this->settingRepository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, 'my-theme'],
        ]);

        $scannedTheme = $this->createTheme('my-theme', isCompatible: true);

        $this->cacheItem->method('isHit')->willReturn(false);
        $this->cacheItem->expects($this->once())->method('set')->with($scannedTheme);

        $this->cachePool->method('getItem')->willReturnMap([
            ['theme.active.dto', $this->cacheItem],
        ]);
        $this->cachePool->expects($this->once())->method('save')->with($this->cacheItem);

        $themeScanner = $this->createThemeScanner($scannedTheme, 'my-theme');
        $manager = $this->createThemeManager($themeScanner);

        $result = $manager->getActiveTheme();

        $this->assertSame($scannedTheme, $result);
    }

    public function testGetActiveThemeReturnsFallbackWhenThemeNotFound(): void
    {
        $this->settingRepository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, 'non-existent-theme'],
        ]);

        $this->cacheItem->method('isHit')->willReturn(false);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($this->callback(function (Theme $theme): bool {
                $this->assertTrue($theme->isFallback);
                $this->assertSame('non-existent-theme', $theme->requestedIdentifier);
                $this->assertSame('not_found', $theme->fallbackReason);

                return true;
            }));

        $this->cachePool->method('getItem')->willReturnMap([
            ['theme.active.dto', $this->cacheItem],
        ]);
        $this->cachePool->expects($this->once())->method('save')->with($this->cacheItem);

        $themeScanner = $this->createThemeScanner(null, 'non-existent-theme');
        $manager = $this->createThemeManager($themeScanner);

        $result = $manager->getActiveTheme();

        $this->assertTrue($result->isFallback);
        $this->assertSame('not_found', $result->fallbackReason);
    }

    public function testGetActiveThemeReturnsFallbackWhenThemeIsIncompatible(): void
    {
        $this->settingRepository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, 'incompatible-theme'],
        ]);

        $incompatibleTheme = $this->createTheme('incompatible-theme', isCompatible: false, requiredVersion: '2.0.0');

        $this->cacheItem->method('isHit')->willReturn(false);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($this->callback(function (Theme $theme): bool {
                $this->assertTrue($theme->isFallback);
                $this->assertSame('incompatible-theme', $theme->requestedIdentifier);
                $this->assertSame('incompatible_version', $theme->fallbackReason);
                $this->assertSame('2.0.0', $theme->requiredInachisVersion);

                return true;
            }));

        $this->cachePool->method('getItem')->willReturnMap([
            ['theme.active.dto', $this->cacheItem],
        ]);

        $themeScanner = $this->createThemeScanner($incompatibleTheme, 'incompatible-theme');
        $manager = $this->createThemeManager($themeScanner);

        $result = $manager->getActiveTheme();

        $this->assertTrue($result->isFallback);
        $this->assertSame('incompatible_version', $result->fallbackReason);
    }

    public function testIsThemeInstalledReturnsTrueWhenThemeExists(): void
    {
        $theme = $this->createTheme('installed-theme');
        $themeScanner = $this->createThemeScanner($theme, 'installed-theme');

        $manager = $this->createThemeManager($themeScanner);

        $this->assertTrue($manager->isThemeInstalled('installed-theme'));
    }

    public function testIsThemeInstalledReturnsFalseWhenThemeDoesNotExist(): void
    {
        $themeScanner = $this->createThemeScanner(null, 'missing-theme');

        $manager = $this->createThemeManager($themeScanner);

        $this->assertFalse($manager->isThemeInstalled('missing-theme'));
    }

    public function testGetDefaultThemePath(): void
    {
        $manager = $this->createThemeManager();

        $this->assertSame(
            $this->projectDir.'/templates/themes/default',
            $manager->getDefaultThemePath(),
        );
    }

    public function testGetActiveThemePathAndWebPath(): void
    {
        $this->settingRepository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, 'default'],
        ]);

        $manager = $this->createThemeManager();

        $expectedPath = $this->projectDir.'/templates/themes/default';
        $this->assertSame($expectedPath, $manager->getActiveThemePath());
        $this->assertSame($expectedPath.'/web', $manager->getActiveThemeWebPath());
    }

    public function testGetAssetPathFormatsPathCorrectly(): void
    {
        $this->settingRepository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, 'custom-theme'],
        ]);

        $manager = $this->createThemeManager();

        $this->assertSame(
            '/themes/custom-theme/assets/css/style.css',
            $manager->getAssetPath('/css/style.css'),
        );
    }

    private function createThemeManager(?ThemeScanner $themeScanner = null): ThemeManager
    {
        $scanner = $themeScanner ?? $this->createThemeScanner();

        return new ThemeManager(
            $this->settingRepository,
            $scanner,
            $this->cachePool,
            $this->projectDir,
        );
    }

    private function createThemeScanner(?Theme $themeToReturn = null, string $identifier = 'default'): ThemeScanner
    {
        $reflection = new \ReflectionClass(ThemeScanner::class);

        if (!$reflection->isFinal()) {
            $scanner = $this->createStub(ThemeScanner::class);
            $scanner->method('getTheme')->willReturnMap([
                [$identifier, $themeToReturn],
            ]);

            return $scanner;
        }

        $scanner = $reflection->newInstanceWithoutConstructor();

        $current = $reflection;
        while (false !== $current) {
            foreach ($current->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isInitialized($scanner)) {
                    continue;
                }

                $propName = $prop->getName();
                $type = $prop->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if ('array' === $typeName || in_array($propName, ['packages', 'items', 'themes', 'packageCache'], true)) {
                    $packages = null !== $themeToReturn ? [
                        $identifier => $themeToReturn,
                        strtolower($identifier) => $themeToReturn,
                        0 => $themeToReturn,
                    ] : [];
                    $prop->setValue($scanner, $packages);
                } elseif (CacheItemPoolInterface::class === $typeName || 'cache' === $propName) {
                    $cachePool = $this->createStub(CacheItemPoolInterface::class);
                    $cacheItem = $this->createStub(CacheItemInterface::class);
                    if (null !== $themeToReturn) {
                        $cacheItem->method('isHit')->willReturn(true);
                        $cacheItem->method('get')->willReturn([
                            $identifier => $themeToReturn,
                            strtolower($identifier) => $themeToReturn,
                        ]);
                    } else {
                        $cacheItem->method('isHit')->willReturn(false);
                    }
                    $cachePool->method('getItem')->willReturn($cacheItem);
                    $prop->setValue($scanner, $cachePool);
                } elseif ('string' === $typeName || 'projectDir' === $propName) {
                    $prop->setValue($scanner, $this->projectDir);
                } elseif ($typeName && (class_exists($typeName) || interface_exists($typeName))) {
                    $depReflection = new \ReflectionClass($typeName);
                    if ($depReflection->isFinal()) {
                        $prop->setValue($scanner, $depReflection->newInstanceWithoutConstructor());
                    } else {
                        $prop->setValue($scanner, $this->createStub($typeName));
                    }
                }
            }
            $current = $current->getParentClass();
        }

        return $scanner;
    }

    private function createTheme(
        string $identifier = 'custom-theme',
        bool $isCompatible = true,
        string $requiredVersion = '1.0.0',
    ): Theme {
        $theme = new Theme(
            identifier: $identifier,
            name: 'Test Theme',
            version: '1.0.0',
            author: 'Inachis',
            description: 'Test Theme Description',
            homepage: '',
            license: '',
            path: $this->projectDir.'/templates/themes/'.$identifier,
        );

        $theme->isCompatible = $isCompatible;
        $theme->requiredInachisVersion = $requiredVersion;

        return $theme;
    }
}
