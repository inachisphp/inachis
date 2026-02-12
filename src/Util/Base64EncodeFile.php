<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Util;

/**
 * Base64 encode file
 */
class Base64EncodeFile
{
    /**
     * Encode file
     *
     * @param string $filename
     * @return string
     */
    public static function encode(string $filename): string
    {
        $projectDir = realpath(__DIR__ . '/../../');
        if ($projectDir === false) {
            return '';
        }

        $fullPath = realpath($projectDir . '/' . ltrim('/' . $filename));
        if ($fullPath === false || !str_starts_with($fullPath, $projectDir)) {
            return '';
        }
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $contents = file_get_contents($fullPath);
        if ($contents === false) {
            return '';
        }
        return 'data:image/' . $type . ';base64,' . base64_encode($contents);
    }
}