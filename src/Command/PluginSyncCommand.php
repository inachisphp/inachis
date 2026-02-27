<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Command;

use Inachis\Service\Plugin\PluginManager;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'inachis:plugin:sync')]
final class PluginSyncCommand extends Command
{
    public function __construct(
        private PluginManager $pluginManager,
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private DependencyFactory $migrationFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Sync all installed plugins: run install/update hooks and migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting plugin sync...</info>');

        foreach ($this->pluginManager->getInstalledPlugins() as $pluginClass) {
            $output->writeln("Processing plugin: $pluginClass");

            if ($this->pluginManager->isEnabled($pluginClass)) {
                $installer = $this->pluginManager->getInstaller($pluginClass);

                $version = $this->pluginManager->getVersion($pluginClass);

                if ($version === null) {
                    $output->writeln("Installing plugin $pluginClass...");
                    $installer->install();
                    $this->pluginManager->setVersion($pluginClass, '1.0.0');
                } else {
                    $latestVersion = $this->pluginManager->getLatestVersion($pluginClass);
                    if (version_compare($version, $latestVersion, '<')) {
                        $output->writeln("Updating plugin $pluginClass from $version to $latestVersion...");
                        $installer->update($version, $latestVersion);
                        $this->pluginManager->setVersion($pluginClass, $latestVersion);
                    } else {
                        $output->writeln("Plugin $pluginClass is up to date.");
                    }
                }

                // Run migrations for this plugin
                $output->writeln("Running migrations for $pluginClass...");
                $this->runPluginMigrations($pluginClass, $output);
            } else {
                $output->writeln("Plugin $pluginClass is disabled; skipping.");
            }
        }

        $output->writeln('<info>Plugin sync completed!</info>');

        return Command::SUCCESS;
    }

    private function runPluginMigrations(string $pluginClass, OutputInterface $output): void
    {
        // You can configure your migrations paths to include plugin migrations dynamically
        // For example, scan src/Resources/migrations inside each plugin bundle
        $migrationPlan = $this->migrationFactory->getMigrationPlanCalculator()->getPlanUntilLatest();

        if (count($migrationPlan) === 0) {
            $output->writeln("No migrations to run for $pluginClass.");
            return;
        }

        foreach ($migrationPlan as $migration) {
            $output->writeln("Running migration: " . $migration->getVersion()->getVersion());
            $migration->execute($this->migrationFactory->getConnection());
        }
    }
}