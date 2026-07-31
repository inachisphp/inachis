<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater;

final readonly class ReleaseInstance
{
    public function __construct(
        public string $identifier,
        public string $version,
        public string $path,
    ) {}
}
