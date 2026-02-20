<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Inachis\Service\System\Maintenance\MaintenanceManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Allows the templates to know if maintenance mode is enabled
 */
class MaintenanceExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @param MaintenanceManager $maintenance
     */
    public function __construct(
        private MaintenanceManager $maintenance
    ) {}

    /**
     * Allow Twig templates to see if maintenance mode is enabled
     *
     * @return array<string,bool>
     */
    public function getGlobals(): array
    {
        return [
            'maintenance_enabled' => $this->maintenance->isEnabled(),
        ];
    }
}