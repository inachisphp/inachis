<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\MySQL81Platform;
use Doctrine\DBAL\Platforms\MySQL82Platform;
use Doctrine\DBAL\Platforms\MySQL83Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

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
            // MySQL variants
            $platform instanceof MySQLPlatform,
            $platform instanceof MySQL57Platform,
            $platform instanceof MySQL80Platform,
            $platform instanceof MySQL81Platform,
            $platform instanceof MySQL82Platform,
            $platform instanceof MySQL83Platform,
            $platform instanceof MySQL84Platform => 'mysql',

            // MariaDB
            $platform instanceof MariaDBPlatform => 'mariadb',

            // PostgreSQL
            $platform instanceof PostgreSQLPlatform => 'postgresql',

            // SQL Server
            $platform instanceof SQLServerPlatform => 'sqlserver',

            // SQLite
            $platform instanceof SqlitePlatform => 'sqlite',

            // Unknown
            default => 'unknown',
        };
    }
}
