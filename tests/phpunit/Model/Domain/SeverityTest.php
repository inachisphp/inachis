<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\Domain;

use Inachis\Model\Domain\Severity;
use PHPUnit\Framework\TestCase;

final class SeverityTest extends TestCase
{
    public function testEnumCasesExist(): void
    {
        $this->assertSame('error', Severity::Error->value);
        $this->assertSame('warning', Severity::Warning->value);
        $this->assertSame('info', Severity::Info->value);
    }

    public function testCasesReturnsAllCases(): void
    {
        $this->assertSame(
            [
                Severity::Error,
                Severity::Warning,
                Severity::Info,
            ],
            Severity::cases(),
        );
    }

    public function testFromReturnsCorrectCase(): void
    {
        $this->assertSame(
            Severity::Error,
            Severity::from('error'),
        );

        $this->assertSame(
            Severity::Warning,
            Severity::from('warning'),
        );

        $this->assertSame(
            Severity::Info,
            Severity::from('info'),
        );
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(
            Severity::tryFrom('invalid'),
        );
    }

    public function testEnumNamesAreCorrect(): void
    {
        $this->assertSame('Error', Severity::Error->name);
        $this->assertSame('Warning', Severity::Warning->name);
        $this->assertSame('Info', Severity::Info->name);
    }
}
