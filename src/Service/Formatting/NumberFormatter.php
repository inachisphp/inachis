<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Formatting;

final class NumberFormatter
{
    /**
     * Convert bytes to human-readable format.
     * 
     * @param int|float $bytes
     * @return string
     */
    public static function formatBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Convert seconds to human-readable format (seconds or minutes if >= 60s).
     * 
     * @param int $seconds
     * @return string
     */
    public static function formatSeconds(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = floor($seconds / 60);
        $remaining = $seconds % 60;
        return $remaining > 0 ? "{$minutes} mins {$remaining} secs" : "{$minutes} mins";
    }
}