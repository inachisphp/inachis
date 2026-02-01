<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Util;

use DateInvalidTimeZoneException;
use DateTimeZone;

class TimezoneChoices
{
    /**
     * @throws DateInvalidTimeZoneException
     */
    public function getTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();
        $now = new \DateTimeImmutable('now');

        $choices = [];

        foreach ($timezones as $tz) {
            $offset = new DateTimeZone($tz)->getOffset($now);
            $hours  = intdiv($offset, 3600);
            $minutes = abs(($offset % 3600) / 60);

            $label = sprintf(
                '(GMT%+03d:%02d) %s',
                $hours,
                $minutes,
                str_replace('_', ' ', $tz)
            );

            $choices[$label] = $tz;
        }

        // Sort by offset then by name
        uksort($choices, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $choices;
    }
}