<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Trait for obtaining the database platform name. This used to be obtainable prior to DBAL 4.x using
 * ->getName
 */
trait DatabasePlatformTrait
{
    /**
     * Returns a friendly platform name for database
     *
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getDatabasePlatformName(AbstractPlatform $platform): string
    {
        return match (true) {
            str_contains(strtolower($platform::class), 'mysql') => 'mysql',
            str_contains(strtolower($platform::class), 'mariadb') => 'mariadb',
            str_contains(strtolower($platform::class), 'postgresql') => 'postgresql',
            str_contains(strtolower($platform::class), 'sqlserver') => 'sqlserver',
            str_contains(strtolower($platform::class), 'sqlite') => 'sqlite',
            default => 'unknown',
        };
    }
}
