<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Updater;

use RuntimeException;

final class IncompatibleVersionException extends RuntimeException
{
    public static function minimumVersionNotMet(
        string $currentVersion,
        string $targetVersion,
        string $minimumRequiredVersion
    ): self {
        return new self(sprintf(
            'Cannot update directly to v%s from v%s. Minimum required version for this release is v%s.',
            $targetVersion,
            $currentVersion,
            $minimumRequiredVersion
        ));
    }
}
