<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Updater\Migration;

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Executes pending migrations found in the newly extracted release directory.
     *
     * @param string $releasePath Path to the extracted release
     * @param string $migrationsNamespace Namespace of the release migrations (e.g., 'Inachis\Migrations')
     * @param string $relativeMigrationsDir Directory containing migration files relative to release root
     * @return string|null The target migration version executed up to (used for rollback target if needed)
     */
    public function migrate(
        string $releasePath,
        string $migrationsNamespace = 'Inachis\Migrations',
        string $relativeMigrationsDir = 'src/Migrations'
    ): ?string {
        $migrationsDir = $releasePath . DIRECTORY_SEPARATOR . ltrim($relativeMigrationsDir, '/\\');

        if (!is_dir($migrationsDir)) {
            // No migrations directory in this release
            return null;
        }

        $dependencyFactory = $this->createDependencyFactory($migrationsNamespace, $migrationsDir);
        $aliasResolver = $dependencyFactory->getVersionAliasResolver();
        $planCalculator = $dependencyFactory->getMigrationPlanCalculator();

        // Resolve the "latest" target version alias
        $latestVersion = $aliasResolver->resolveVersionAlias('latest');

        // Calculate the plan up to 'latest'
        $plan = $planCalculator->getPlanUntilVersion($latestVersion);

        if (count($plan) === 0) {
            // Database is already up to date
            return (string) $latestVersion;
        }

        $configuration = new MigratorConfiguration();
        // Wrap migrations in a single transaction where supported
        $configuration->setAllOrNothing(true);

        try {
            $migrator = $dependencyFactory->getMigrator();
            $migrator->migrate($plan, $configuration);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf('Doctrine Migration failed: %s', $exception->getMessage()),
                0,
                $exception
            );
        }

        return (string) $latestVersion;
    }

    /**
     * Rollback migrations to a specific previous version target.
     */
    public function rollbackTo(
        string $targetVersion,
        string $releasePath,
        string $migrationsNamespace = 'Inachis\Migrations',
        string $relativeMigrationsDir = 'src/Migrations'
    ): void {
        $migrationsDir = $releasePath . DIRECTORY_SEPARATOR . ltrim($relativeMigrationsDir, '/\\');

        if (!is_dir($migrationsDir)) {
            return;
        }

        $dependencyFactory = $this->createDependencyFactory($migrationsNamespace, $migrationsDir);
        $planCalculator = $dependencyFactory->getMigrationPlanCalculator();

        try {
            $plan = $planCalculator->getPlanUntilVersion(new Version($targetVersion));
            $configuration = new MigratorConfiguration();
            $configuration->setAllOrNothing(true);

            $migrator = $dependencyFactory->getMigrator();
            $migrator->migrate($plan, $configuration);
        } catch (Throwable $exception) {
            error_log(sprintf('Failed to rollback Doctrine migrations to version %s: %s', $targetVersion, $exception->getMessage()));
        }
    }

    /**
     * Helper to instantiate Doctrine Migrations DependencyFactory.
     */
    private function createDependencyFactory(string $namespace, string $directory): DependencyFactory
    {
        $config = new ConfigurationArray([
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
            ],
            'migrations_paths' => [
                $namespace => $directory,
            ],
            'all_or_nothing' => true,
            'check_database_platform' => true,
        ]);

        return DependencyFactory::fromEntityManager(
            $config,
            new ExistingEntityManager($this->entityManager)
        );
    }
}
