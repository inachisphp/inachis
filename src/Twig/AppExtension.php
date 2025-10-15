<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class AppExtension
 * @package App\Twig
 */
class AppExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('activeMenu', [$this, 'activeMenuFilter']),
        ];
    }

    /**
     * @param string $menuOption
     * @param string|null $selectedOption
     * @return string
     */
    public function activeMenuFilter(string $menuOption, ?string $selectedOption = ''): string
    {
        return !empty($menuOption) && $menuOption == $selectedOption ? 'menu__active' : '';
    }

    /**
     * @param int $bytes
     * @param bool $trimTrailing
     * @return string
     */
    public function bytesToMinimumUnit(int $bytes, bool $trimTrailing = false): string
    {
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = (int) floor(log($bytes)/log(1024));
        $result = sprintf('%.2f', ($bytes/pow(1024, floor($exp))));
        if ($trimTrailing) {
            return sprintf(
                '%s %s', rtrim(rtrim($result, '0'), '.'),
                $symbols[$exp]
            );
        }
        return sprintf('%s %s', $result, $symbols[$exp]);
    }
}
