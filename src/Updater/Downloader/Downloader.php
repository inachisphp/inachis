<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater\Downloader;

use RuntimeException;

final class Downloader
{
    public function download(
        string $url,
        string $destination,
    ): void {
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'follow_location' => true,
            ],
        ]);

        $source = fopen(
            $url,
            'rb',
            false,
            $context
        );

        if ($source === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to download "%s".',
                    $url
                )
            );
        }

        $temporary = $destination . '.download';

        try {
            $target = fopen(
                $temporary,
                'wb'
            );

            if ($target === false) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to create "%s".',
                        $temporary
                    )
                );
            }

            $bytes = stream_copy_to_stream(
                $source,
                $target
            );

            fclose($target);
            fclose($source);

            if ($bytes === false) {
                throw new RuntimeException(
                    sprintf(
                        'Failed downloading "%s".',
                        $url
                    )
                );
            }

            if (!rename($temporary, $destination)) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to move download to "%s".',
                        $destination
                    )
                );
            }

        } catch (\Throwable $exception) {
            fclose($source);

            if (file_exists($temporary)) {
                unlink($temporary);
            }

            throw $exception;
        }
    }
}
