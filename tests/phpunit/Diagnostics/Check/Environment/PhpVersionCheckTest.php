<?php

namespace Inachis\Tests\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\PhpVersionCheck;
use PHPUnit\Framework\TestCase;

final class PhpVersionCheckTest extends TestCase
{
    public function testRunOk(): void
    {
        $check = new PhpVersionCheck('8.3.0');
        $result = $check->run();

        $this->assertSame('ok', $result->status);
        $this->assertStringContainsString('Detected PHP version: 8.3.0', $result->details);
        $this->assertNull($result->recommendation);
    }

    public function testRunWarning(): void
    {
        $check = new PhpVersionCheck('8.1.0');
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertStringContainsString('Detected PHP version: 8.1.0', $result->details);
        $this->assertNotNull($result->recommendation);
    }

    public function testMetadata(): void
    {
        $check = new PhpVersionCheck();
        $this->assertSame('php_version', $check->getId());
        $this->assertSame('PHP Version', $check->getLabel());
        $this->assertSame('Environment', $check->getSection());
    }
}
