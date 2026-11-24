<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Updater;

interface SymlinkManagerInterface
{
    public function switchCurrent(
        string $currentLink,
        string $targetPath,
    ): void;
}
