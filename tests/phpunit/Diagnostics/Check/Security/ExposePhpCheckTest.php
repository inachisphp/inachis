<?php

namespace Inachis\Tests\Diagnostics\Check\Security;

use Inachis\Diagnostics\Check\Security\ExposePhpCheck;
use PHPUnit\Framework\TestCase;

final class ExposePhpCheckTest extends TestCase
{
    public static array $ini_values = [];

    protected function setUp(): void
    {
        self::$ini_values = [];
    }

    public function testRunOkWithOff(): void
    {
        self::$ini_values['expose_php'] = 'Off';
        
        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('ok', $result->status);
        $this->assertNull($result->recommendation);
    }

    public function testRunOkWithZero(): void
    {
        self::$ini_values['expose_php'] = '0';
        
        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('ok', $result->status);
    }

    public function testRunWarning(): void
    {
        self::$ini_values['expose_php'] = 'On';
        
        $check = new ExposePhpCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
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

function ini_get(string $option)
{
    return \Inachis\Tests\Diagnostics\Check\Security\ExposePhpCheckTest::$ini_values[$option] ?? \ini_get($option);
}
