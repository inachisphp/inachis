<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Exception\User;

use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use PHPUnit\Framework\TestCase;

final class CannotRemoveLastAdministratorExceptionTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new CannotRemoveLastAdministratorException();

        self::assertInstanceOf(
            CannotRemoveLastAdministratorException::class,
            $instance,
        );
    }
}
