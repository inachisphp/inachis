<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Updater\Provider;

use Inachis\Updater\Release\Manifest;

interface ReleaseProviderInterface
{
    public function latest(): Manifest;

    public function version(string $version): Manifest;

    public function download(
        Manifest $manifest,
        string $destination,
    ): void;
}
