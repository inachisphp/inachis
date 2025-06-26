<?php

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
     * @return string
     */
    public function bytesToMinimumUnit(int $bytes): string
    {
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = (int) floor(log($bytes)/log(1024));

        return sprintf('%.2f '. $symbols[$exp], ($bytes/pow(1024, floor($exp))));
    }
}
