<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater\Verify;

use RuntimeException;

final class ReleaseVerifier
{
    public function verify(
        string $file,
        string $expectedChecksum,
    ): void {
        if (!is_file($file)) {
            throw new RuntimeException(
                sprintf(
                    'Release archive "%s" does not exist.',
                    $file
                )
            );
        }

        if (!is_readable($file)) {
            throw new RuntimeException(
                sprintf(
                    'Release archive "%s" is not readable.',
                    $file
                )
            );
        }

        $checksum = hash_file(
            'sha256',
            $file
        );

        if ($checksum === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to calculate checksum for "%s".',
                    $file
                )
            );
        }

        if (!hash_equals(
            strtolower($expectedChecksum),
            strtolower($checksum)
        )) {
            throw new RuntimeException(
                sprintf(
                    'Release checksum mismatch for "%s". Expected %s, got %s.',
                    $file,
                    $expectedChecksum,
                    $checksum
                )
            );
        }
    }
}
