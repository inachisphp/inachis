<?php

namespace Inachis\Tests\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\PhpSapiCheck;
use PHPUnit\Framework\TestCase;

final class PhpSapiCheckTest extends TestCase
{
    public static ?string $sapi_name = null;

    protected function setUp(): void
    {
        self::$sapi_name = null;
    }

    public function testRunOk(): void
    {
        self::$sapi_name = 'fpm-fcgi';
        
        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('ok', $result->status);
        $this->assertSame('fpm-fcgi', $result->value);
    }

    public function testRunWarning(): void
    {
        self::$sapi_name = 'cli-server';
        
        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertNotNull($result->recommendation);
    }

    public function testMetadata(): void
    {
        $check = new PhpSapiCheck();
        $this->assertSame('php_sapi', $check->getId());
        $this->assertSame('PHP SAPI', $check->getLabel());
        $this->assertSame('Environment', $check->getSection());
    }
}

namespace Inachis\Diagnostics\Check\Environment;

function php_sapi_name()
{
    return \Inachis\Tests\Diagnostics\Check\Environment\PhpSapiCheckTest::$sapi_name ?? \php_sapi_name();
}
