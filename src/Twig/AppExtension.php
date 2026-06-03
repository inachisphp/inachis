<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Inachis\Entity\User\User;
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

    /**
     * Format a date to the local timezone
     *
     * @param \DateTimeImmutable $date
     * @param string $format
     * @return string
     */
    public function formatLocalTime(\DateTimeImmutable $date, string $format = 'Y-m-d H:i'): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $date->format($format);
        }
        $timezone = new \DateTimeZone($user->getPreferences()?->getTimezone() ?? 'UTC');
        $localisedDate = (clone $date)->setTimezone($timezone);
        return $localisedDate->format($format);
    }

    /**
     * Returns the active menu option
     *
     * @param string $menuOption
     * @param string|null $selectedOption
     * @return string
     */
    public function activeMenuFilter(string $menuOption, ?string $selectedOption = ''): string
    {
        return !empty($menuOption) && $menuOption == $selectedOption ? 'menu__active' : '';
    }

    /**
     * Convert bytes to the smallest unit
     *
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
