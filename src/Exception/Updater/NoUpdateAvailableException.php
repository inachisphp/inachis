<?php

namespace Inachis\Exception\Updater;

use RuntimeException;

final class NoUpdateAvailableException extends RuntimeException
{
    public static function alreadyUpToDate(string $currentVersion): self
    {
        return new self(sprintf(
            'The application is already running the latest version (v%s).',
            $currentVersion
        ));
    }

    public static function downgradeNotSupported(string $currentVersion, string $targetVersion): self
    {
        return new self(sprintf(
            'Target version v%s is older than current version v%s. Downgrades are not supported.',
            $targetVersion,
            $currentVersion
        ));
    }
}
