<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Plugin;

interface PluginInterface
{
    public function getMetadata(): PluginMetadata;

    public function boot(PluginContext $context): void;
}
