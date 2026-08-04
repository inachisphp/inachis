<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Updater;

final class NoUpdateAvailableException extends \RuntimeException
{
    public static function alreadyUpToDate(string $currentVersion): self
    {
        return new self(sprintf(
            'The application is already running the latest version (v%s).',
            $currentVersion,
        ));
    }

    public static function downgradeNotSupported(string $currentVersion, string $targetVersion): self
    {
        return new self(sprintf(
            'Target version v%s is older than current version v%s. Downgrades are not supported.',
            $targetVersion,
            $currentVersion,
        ));
    }
}
