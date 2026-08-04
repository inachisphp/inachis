<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form\Provider;

/**
 * TimezoneChoices class.
 */
class TimezoneChoices
{
    /**
     * Get timezone choices.
     *
     * @return array<string, string>
     *
     * @throws \DateInvalidTimeZoneException
     */
    public function getTimezones(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();
        $now = new \DateTimeImmutable('now');

        $choices = [];

        foreach ($timezones as $tz) {
            $offset = new \DateTimeZone($tz)->getOffset($now);
            $hours = intdiv($offset, 3600);
            $minutes = abs(($offset % 3600) / 60);

            $label = sprintf(
                '(GMT%+03d:%02d) %s',
                $hours,
                $minutes,
                str_replace('_', ' ', $tz),
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
