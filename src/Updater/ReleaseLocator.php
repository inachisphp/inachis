<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Updater;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ReleaseLocator
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $installRoot,
    ) {}

    public function create(
        string $version,
    ): ReleaseInstance {
        $identifier = sprintf(
            '%s-%s',
            date('YmdHis'),
            $version
        );

        return new ReleaseInstance(
            identifier: $identifier,
            version: $version,
            path: $this->releasesDirectory()
                . DIRECTORY_SEPARATOR
                . $identifier,
        );
    }

    public function releasesDirectory(): string
    {
        return $this->installRoot
            . DIRECTORY_SEPARATOR
            . 'releases';
    }

    public function sharedDirectory(): string
    {
        return $this->installRoot
            . DIRECTORY_SEPARATOR
            . 'shared';
    }

    public function currentLink(): string
    {
        return $this->installRoot
            . DIRECTORY_SEPARATOR
            . 'current';
    }
}
