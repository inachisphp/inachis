<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Tests\phpunit\Security\Authentication;

use Inachis\Security\Authentication\TotpRequirementChecker;
use PHPUnit\Framework\TestCase;

final class TotpRequirementCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new TotpRequirementChecker();

        self::assertInstanceOf(
            TotpRequirementChecker::class,
            $instance,
        );
    }
}
