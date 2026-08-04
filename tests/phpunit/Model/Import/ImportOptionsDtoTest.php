<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Import;

use Inachis\Model\Import\ImportOptionsDto;
use PHPUnit\Framework\TestCase;

final class ImportOptionsDtoTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new ImportOptionsDto();

        self::assertInstanceOf(
            ImportOptionsDto::class,
            $instance
        );
    }
}