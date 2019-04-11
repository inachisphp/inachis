<?php

namespace App\Validator;

use App\Exception\InvalidTimezoneException;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Class DateValidator
 * @package App\Validator
 */
class DateValidator
{
    /**
     * Tests if a timezone is valid - errors if it is not, otherwise returns the timezone. If user input is possible
     * it should gracefully handle this exception.
     *
     * @param string|null $timezone The timezone to validate
     * @return string The validated timezone
     * @throws InvalidTimezoneException
     */
    public static function validateTimezone(?string $timezone): string
    {
        if (empty($timezone)) {
            throw new InvalidArgumentException('The timezone can not be empty.');
        }

        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            throw new InvalidTimezoneException(sprintf(
                '"%s" is not a valid timezone',
                $timezone
            ));
        }

        return $timezone;
    }
}
