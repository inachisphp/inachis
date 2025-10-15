<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Util;

class RandomColorPicker
{
    /**
     * @var array|string[] The list of available colours to use for profile icon backgrounds
     */
    private static array $colors = [ '#099bdd', '#f90', '#090', '#dd0909', '#8409dd', '#dd8709' ];

    /**
     * @return string
     */
    public static function generate(): string
    {
        return self::$colors[array_rand(self::$colors)];
    }

    /**
     * @return array|string[]
     */
    public static function getAll(): array
    {
        return self::$colors;
    }

    /**
     * @param string $color
     * @return bool
     */
    public static function isValid(string $color): bool
    {
        return in_array($color, self::$colors);
    }
}