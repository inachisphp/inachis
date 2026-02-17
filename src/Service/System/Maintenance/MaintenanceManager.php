<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Maintenance;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

/**
 * Manages the maintenance mode
 */
class MaintenanceManager
{
    /**
     * Constructor
     *
     * @param string $projectDir The project directory
     * @param Environment $twig The twig environment
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private Environment $twig
    ) {}

    /**
     * Check if maintenance mode is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return file_exists($this->projectDir.'/var/maintenance.lock');
    }

    /**
     * Enable maintenance mode
     *
     * @return void
     */
    public function enable(): void
    {
        touch($this->projectDir.'/var/maintenance.lock');
    }

    /**
     * Disable maintenance mode
     *
     * @return void
     */
    public function disable(): void
    {
        @unlink($this->projectDir.'/var/maintenance.lock');
    }

    /**
     * Get the maintenance configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        $file = $this->projectDir.'/var/maintenance.json';
        if (!file_exists($file)) {
            return [
                'message' => 'Our site is currently undergoing scheduled maintenance.',
                'estimated_downtime' => '1 hour',
                'allowed_ips' => [],
                'retry_after' => 3600,
            ];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }

    /**
     * Save the maintenance configuration
     *
     * @param array $config The maintenance configuration
     * @return void
     */
    public function saveConfig(array $config): void
    {
        $file = $this->projectDir.'/var/maintenance.json';
        file_put_contents($file, json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Generate the static maintenance page
     *
     * @param array $config The maintenance configuration
     * @return void
     */
    public function generateStaticPage(array $config): void
    {
        $html = $this->twig->render('web/maintenance_template.html.twig', $config);
        file_put_contents($this->projectDir.'/public/maintenance.html', $html);
    }
}