<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\API\User;

use Inachis\Controller\API\User\CalculatePasswordStrength;
use PHPUnit\Framework\TestCase;

final class CalculatePasswordStrengthTest extends TestCase
{

    public function testCanBeInstantiated(): void
    {
        $instance = new CalculatePasswordStrength();

        self::assertInstanceOf(
            CalculatePasswordStrength::class,
            $instance
        );
    }
}