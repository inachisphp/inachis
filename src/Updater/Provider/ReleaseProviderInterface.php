<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

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
