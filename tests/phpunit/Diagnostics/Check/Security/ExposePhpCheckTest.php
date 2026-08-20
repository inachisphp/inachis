<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\ExposePhpCheck;
use PHPUnit\Framework\TestCase;

final class ExposePhpCheckTest extends TestCase
{
    public static array $iniValues = [];

    protected function setUp(): void
    {
        self::$iniValues = [];
    }

    public function testRunOkWithOff(): void
    {
        self::$iniValues['expose_php'] = 'Off';

        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('expose_php', $result->id);
        $this->assertSame('PHP Expose Version', $result->label);
        $this->assertSame('ok', $result->status);
        $this->assertSame('Off', $result->value);
        $this->assertSame(
            'PHP version exposure is disabled.',
            $result->details,
        );
        $this->assertNull($result->recommendation);
        $this->assertSame('Security', $result->section);
    }

    public function testRunOkWithZero(): void
    {
        self::$iniValues['expose_php'] = '0';

        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('ok', $result->status);
        $this->assertSame('0', $result->value);
        $this->assertSame(
            'PHP version exposure is disabled.',
            $result->details,
        );
        $this->assertNull($result->recommendation);
    }

    public function testRunWarning(): void
    {
        self::$iniValues['expose_php'] = 'On';

        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame('On', $result->value);
        $this->assertSame(
            'PHP exposes its version in headers, which is a security risk.',
            $result->details,
        );
        $this->assertSame(
            'Set expose_php=Off in php.ini.',
            $result->recommendation,
        );
        $this->assertSame('Security', $result->section);
    }

    public function testRunWarningWithUnexpectedValue(): void
    {
        self::$iniValues['expose_php'] = 'unexpected';

        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame('unexpected', $result->value);
        $this->assertNotNull($result->recommendation);
    }

    public function testRunHandlesEmptyValue(): void
    {
        self::$iniValues['expose_php'] = '';

        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame('', $result->value);
        $this->assertNotNull($result->recommendation);
    }

    public function testMetadata(): void
    {
        $check = new ExposePhpCheck();

        $this->assertSame('expose_php', $check->getId());
        $this->assertSame('PHP Expose Version', $check->getLabel());
        $this->assertSame('Security', $check->getSection());
        $this->assertSame('medium', $check->getSeverity());
    }
}

namespace Inachis\Diagnostics\Check\Security;

function ini_get(string $option): string|false
{
    return \Inachis\Tests\phpunit\Diagnostics\Check\Security\ExposePhpCheckTest::$iniValues[$option]
        ?? \ini_get($option);
}
