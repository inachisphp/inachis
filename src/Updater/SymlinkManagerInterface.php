<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater;

interface SymlinkManagerInterface
{
    public function switchCurrent(
        string $currentLink,
        string $targetPath,
    ): void;
}
