<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Service\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Interface for plugin installers.
 */
#[AutoconfigureTag('cms.plugin_installer')]
interface PluginInstallerInterface
{
    /**
     * Install the plugin.
     */
    public function install(): void;
}
