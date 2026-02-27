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
        $this->generateStaticPage($this->getConfig());
    }

    /**
     * Disable maintenance mode
     *
     * @return void
     */
    public function disable(): void
    {
        @unlink($this->projectDir.'/var/maintenance.lock');
        @unlink($this->projectDir.'/public/maintenance.html');
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
     * Save the maintenance configuration using an atomic write.
     *
     * @param array $config The maintenance configuration
     * @return void
     */
    public function saveConfig(array $config): void
    {
        $this->atomicWrite(
            'maintenance.json',
            $this->projectDir . '/var',
            json_encode($config, JSON_PRETTY_PRINT),
            0600,
            LOCK_EX,
        );
    }

    /**
     * Generates the static maintenance page. For security it will use an atomic write
     * and set permissions correctly before writing content to the file and then moving it
     * to the correct location.
     *
     * @param array $config The maintenance configuration
     * @return void
     */
    public function generateStaticPage(array $config): void
    {
        $html = $this->twig->render('web/maintenance_template.html.twig', $config);
        $this->atomicWrite('maintenance.html', $this->projectDir.'/public', $html, 0644);
    }

    /**
     * Write a file using an atomic write operation to avoid concurrency and security
     * issues.
     *
     * @param string $filename The name of the file to write
     * @param string $location The location to write the file to
     * @param string $content The content to write to the file
     * @param int $permissions The permissions to set on the file
     * @param int $flags The flags to use with file_put_contents
     */
    private function atomicWrite($filename, $location, $content, $permissions = 0600, $flags = 0)
    {
        $tmpFile = $this->projectDir . '/var/' . $filename . '.tmp';
        touch($tmpFile);
        chmod($tmpFile, $permissions);
        file_put_contents($tmpFile, $content, $flags);
        rename($tmpFile, $location . '/' . $filename);
    }
}