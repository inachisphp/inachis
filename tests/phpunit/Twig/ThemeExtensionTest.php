<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Twig;

use Inachis\Repository\System\SettingRepository;
use Inachis\Service\Theme\FeatureRegistry;
use Inachis\Service\Theme\ThemeManager;
use Inachis\Service\Theme\ThemeScanner;
use Inachis\Twig\ThemeExtension;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Twig\TwigFunction;

class ThemeExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsRegisteredTwigFunctions(): void
    {
        $themeManager = $this->createThemeManager();
        $featureRegistry = new FeatureRegistry();

        $extension = new ThemeExtension($themeManager, $featureRegistry);
        $functions = $extension->getFunctions();

        $this->assertCount(3, $functions);
        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);

        $functionNames = array_map(
            static fn (TwigFunction $function): string => $function->getName(),
            $functions,
        );

        $this->assertSame(['feature_enabled', 'plugin_enabled', 'theme_asset'], $functionNames);
    }

    public function testFeatureEnabledDelegatesToFeatureRegistry(): void
    {
        $themeManager = $this->createThemeManager();
        $featureRegistry = new FeatureRegistry();
        $featureRegistry->register('sidebar');

        $extension = new ThemeExtension($themeManager, $featureRegistry);

        $this->assertTrue($extension->featureEnabled('sidebar'));
        $this->assertFalse($extension->featureEnabled('dark_mode'));
    }

    public function testPluginEnabledDelegatesToFeatureRegistry(): void
    {
        $themeManager = $this->createThemeManager();
        $featureRegistry = new FeatureRegistry();
        $featureRegistry->register('analytics');

        $extension = new ThemeExtension($themeManager, $featureRegistry);

        $this->assertTrue($extension->pluginEnabled('analytics'));
        $this->assertFalse($extension->pluginEnabled('comments'));
    }

    public function testThemeAssetDelegatesToThemeManager(): void
    {
        $settingRepository = $this->createSettingRepository('custom-theme');
        $themeManager = $this->createThemeManager($settingRepository);
        $featureRegistry = new FeatureRegistry();

        $extension = new ThemeExtension($themeManager, $featureRegistry);

        $this->assertSame('/themes/custom-theme/assets/css/style.css', $extension->themeAsset('css/style.css'));
    }

    private function createThemeManager(?SettingRepository $settingRepository = null): ThemeManager
    {
        $settings = $settingRepository ?? $this->createSettingRepository('default');

        // Instantiates final ThemeScanner without calling constructor
        $themeScanner = (new \ReflectionClass(ThemeScanner::class))->newInstanceWithoutConstructor();
        $cache = $this->createStub(CacheItemPoolInterface::class);

        return new ThemeManager($settings, $themeScanner, $cache, '/tmp');
    }

    private function createSettingRepository(string $activeTheme = 'default'): SettingRepository
    {
        if ((new \ReflectionClass(SettingRepository::class))->isFinal()) {
            return (new \ReflectionClass(SettingRepository::class))->newInstanceWithoutConstructor();
        }

        $repository = $this->createStub(SettingRepository::class);
        $repository->method('getValue')->willReturnMap([
            [ThemeManager::SETTING_ACTIVE_THEME, $activeTheme],
        ]);

        return $repository;
    }
}
