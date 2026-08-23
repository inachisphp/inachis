<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Theme;

use Inachis\Model\System\Theme;
use Inachis\Service\Package\AbstractPackageScanner;

/**
 * @extends AbstractPackageScanner<Theme>
 */
final readonly class ThemeScanner extends AbstractPackageScanner
{
    protected function cachePrefix(): string
    {
        return 'themes';
    }

    protected function manifestFilename(): string
    {
        return 'theme.yaml';
    }

    protected function packageRoots(): array
    {
        return [
            $this->projectDir.'/templates/themes',
        ];
    }

    protected function createPackage(
        string $path,
        array $manifest,
    ): Theme {
        /** @var array<string> $basePackage */
        $basePackage = $this->createBasePackage(
            $path,
            $manifest,
        );

        $theme = new Theme(...$basePackage);

        $theme->screenshot = $this->findScreenshot($path);

        return $theme;
    }

    /** 
     * @return list<Theme>
     */
    public function getThemes(): array
    {
        return $this->getPackages();
    }

    public function getTheme(string $identifier): ?Theme
    {
        return $this->getPackage($identifier);
    }

    /**
     * @return list<Theme>
     */
    public function rescanThemes(): array
    {
        return $this->rescanPackages();
    }
}
