<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater;

use RuntimeException;
use ZipArchive;

final class ReleaseExtractor
{
    public function extract(
        string $archive,
        string $destination,
    ): void {
        $destination = realpath($destination) ?: $destination;

        if (!is_dir($destination)) {
            if (!mkdir($destination, 0775, true) && !is_dir($destination)) {
                throw new RuntimeException(
                    sprintf('Unable to create release directory "%s".', $destination)
                );
            }
            $destination = realpath($destination);
        }

        $zip = new ZipArchive();
        $result = $zip->open($archive);

        if ($result !== true) {
            throw new RuntimeException(
                sprintf('Unable to open release archive "%s". Error code: %d', $archive, $result)
            );
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if ($filename === false) {
                    continue;
                }

                // Prevent Zip Slip vulnerability
                $targetPath = $destination . DIRECTORY_SEPARATOR . $filename;
                
                // Resolve normalized path check
                $parts = array_filter(explode('/', str_replace('\\', '/', $filename)), strlen(...));
                $p = [];
                foreach ($parts as $part) {
                    if ($part === '.') continue;
                    if ($part === '..') {
                        array_pop($p);
                    } else {
                        $p[] = $part;
                    }
                }
                $normalizedTarget = $destination . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $p);

                if (!str_starts_with($normalizedTarget, $destination)) {
                    throw new RuntimeException(
                        sprintf('Zip slip attempt detected with path: %s', $filename)
                    );
                }

                // Extract individual entry safely
                $zip->extractTo($destination, [$filename]);
            }
        } finally {
            $zip->close();
        }
    }
}
