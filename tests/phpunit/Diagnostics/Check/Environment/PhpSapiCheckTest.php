<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Diagnostics\Check\Environment;

use Inachis\Diagnostics\Check\Environment\PhpSapiCheck;
use PHPUnit\Framework\TestCase;

final class PhpSapiCheckTest extends TestCase
{
    public static ?string $sapiName = null;

    protected function setUp(): void
    {
        self::$sapiName = null;
    }

    public function testRunWithFpmSapiReturnsOk(): void
    {
        self::$sapiName = 'fpm-fcgi';

        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('php_sapi', $result->id);
        $this->assertSame('PHP SAPI', $result->label);
        $this->assertSame('ok', $result->status);
        $this->assertSame('fpm-fcgi', $result->value);
        $this->assertSame('Detected SAPI: fpm-fcgi', $result->details);
        $this->assertNull($result->recommendation);
        $this->assertSame('Environment', $result->section);
    }

    public function testRunWithApacheSapiReturnsOk(): void
    {
        self::$sapiName = 'apache2handler';

        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('ok', $result->status);
        $this->assertSame('apache2handler', $result->value);
        $this->assertSame(
            'Detected SAPI: apache2handler',
            $result->details,
        );
        $this->assertNull($result->recommendation);
    }

    public function testRunWithCliServerSapiReturnsWarning(): void
    {
        self::$sapiName = 'cli-server';

        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('php_sapi', $result->id);
        $this->assertSame('PHP SAPI', $result->label);
        $this->assertSame('warning', $result->status);
        $this->assertSame('cli-server', $result->value);
        $this->assertSame(
            'Detected SAPI: cli-server',
            $result->details,
        );
        $this->assertSame(
            'Recommended SAPI is FPM or Apache2handler for optimal performance.',
            $result->recommendation,
        );
        $this->assertSame('Environment', $result->section);
    }

    public function testRunWithCliSapiReturnsWarning(): void
    {
        self::$sapiName = 'cli';

        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame('cli', $result->value);
        $this->assertSame(
            'Detected SAPI: cli',
            $result->details,
        );
        $this->assertSame(
            'Recommended SAPI is FPM or Apache2handler for optimal performance.',
            $result->recommendation,
        );
    }

    public function testRunWithUnknownSapiReturnsWarning(): void
    {
        self::$sapiName = 'unknown';

        $check = new PhpSapiCheck();
        $result = $check->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame('unknown', $result->value);
        $this->assertSame(
            'Detected SAPI: unknown',
            $result->details,
        );
        $this->assertSame(
            'Recommended SAPI is FPM or Apache2handler for optimal performance.',
            $result->recommendation,
        );
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

/**
 * Test double for the namespaced php_sapi_name() function.
 */
function php_sapi_name(): string
{
    return \Inachis\Tests\phpunit\Diagnostics\Check\Environment\PhpSapiCheckTest::$sapiName
        ?? \php_sapi_name();
}
