<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
            $this->projectDir . '/templates/themes',
        ];
    }

    protected function createPackage(
        string $path,
        array $manifest
    ): Theme {
        $theme = new Theme(
            ...$this->createBasePackage(
                $path,
                $manifest
            )
        );

        $theme->screenshot = $this->findScreenshot($path);

        return $theme;
    }

    public function getThemes(): array
    {
        return $this->getPackages();
    }

    public function getTheme(string $identifier): ?Theme
    {
        return $this->getPackage($identifier);
    }

    public function rescanThemes(): array
    {
        return $this->rescanPackages();
    }
}
