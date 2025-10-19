<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Util;

class Base64EncodeFile
{
    public static function encode(string $filename): string
    {
        $filename = __DIR__ . '/../../' . $filename;
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . base64_encode(file_get_contents($filename));
    }
}