<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\File;

/**
 * Base64 encode file.
 */
class Base64EncodeFile
{
    /**
     * Encode file.
     */
    public static function encode(string $filename): string
    {
        $projectDir = realpath(__DIR__.'/../../');
        if (false === $projectDir) {
            return '';
        }

        $fullPath = realpath($projectDir.'/'.ltrim('/'.$filename));
        if (false === $fullPath || !str_starts_with($fullPath, $projectDir)) {
            return '';
        }
        $type = pathinfo($filename, PATHINFO_EXTENSION);
        $contents = file_get_contents($fullPath);
        if (false === $contents) {
            return '';
        }

        return 'data:image/'.$type.';base64,'.base64_encode($contents);
    }
}
