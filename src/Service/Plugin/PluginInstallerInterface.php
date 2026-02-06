<?php

namespace Inachis\Service\Plugin;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('cms.plugin_installer')]
interface PluginInstallerInterface
{
    public function install(): void;
}
