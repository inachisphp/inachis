<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Twig;

use Inachis\Entity\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class AppExtension.
 */
class AppExtension extends AbstractExtension
{
    private Security $security;

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
     * Format a date to the local timezone.
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
     * Returns the active menu option.
     */
    public function activeMenuFilter(string $menuOption, ?string $selectedOption = ''): string
    {
        return !empty($menuOption) && $menuOption == $selectedOption ? 'menu__active' : '';
    }

    /**
     * Convert bytes to the smallest unit.
     */
    public function bytesToMinimumUnit(int $bytes, bool $trimTrailing = false): string
    {
        if ($bytes < 0) {
            return '0 B';
        }
        $symbols = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];
        $exp = (int) floor(log($bytes) / log(1024));
        $result = sprintf('%.2f', $bytes / pow(1024, floor($exp)));
        if ($trimTrailing) {
            return sprintf(
                '%s %s',
                rtrim(rtrim($result, '0'), '.'),
                $symbols[$exp],
            );
        }

        return sprintf('%s %s', $result, $symbols[$exp]);
    }
}
