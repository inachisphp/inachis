<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Util;

class Base64EncodeFile
{
    public static function encode(string $filename): string
    {
        $projectDir = realpath(__DIR__ . '/../../');
        $fullPath = realpath($projectDir . '/' . ltrim('/' . $filename));
        if ($fullPath === false || !str_starts_with($fullPath, $projectDir)) {
            return '';
        }
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}