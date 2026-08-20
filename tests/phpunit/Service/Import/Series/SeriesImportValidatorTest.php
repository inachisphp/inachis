<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\Import\Series;

use Inachis\Service\Import\Series\SeriesImportValidator;
use PHPUnit\Framework\TestCase;

final class SeriesImportValidatorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new SeriesImportValidator();

        self::assertInstanceOf(
            SeriesImportValidator::class,
            $instance,
        );
    }
}
