<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Class AppExtension
 * @package Inachis\Twig
 */
class AppExtension extends AbstractExtension
{
    private Security $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('activeMenu', [$this, 'activeMenuFilter']),
            new TwigFilter('formatLocalTime', [$this, 'formatLocalTime']),
        ];
    }

    public function formatLocalTime(\DateTimeInterface $date, string $format = 'Y-m-d H:i'): string
    {
        $timezone = new \DateTimeZone($this->security->getUser()->getPreferences()->getTimezone());
        return $date->setTimezone($timezone)->format($format);
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
        if ($bytes < 0) {
            return '0 B';
        }
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = (int) floor(log($bytes) / log(1024));
        $result = sprintf('%.2f', ($bytes / pow(1024, floor($exp))));
        if ($trimTrailing) {
            return sprintf(
                '%s %s',
                rtrim(rtrim($result, '0'), '.'),
                $symbols[$exp]
            );
        }
        return sprintf('%s %s', $result, $symbols[$exp]);
    }
}
