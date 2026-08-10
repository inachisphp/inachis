<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model\System;

use Inachis\Model\System\CspReportDto;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CspReportDtoTest extends TestCase
{
    #[Test]
    public function itStoresConstructorValues(): void
    {
        $rawPayload = [
            'document-uri' => 'https://example.com/page',
            'blocked-uri' => 'https://tracker.example.net/script.js',
            'effective-directive' => 'script-src',
            'violated-directive' => 'script-src-elem',
            'original-policy' => "default-src 'self'",
            'source-file' => 'https://example.com/app.js',
            'line-number' => 42,
            'column-number' => 17,
            'disposition' => 'report',
            'status-code' => 200,
            'referrer' => 'https://example.com/',
            'user-agent' => 'Test Browser',
        ];

        $report = new CspReportDto(
            documentUri: 'https://example.com/page',
            blockedUri: 'https://tracker.example.net/script.js',
            effectiveDirective: 'script-src',
            violatedDirective: 'script-src-elem',
            originalPolicy: "default-src 'self'",
            sourceFile: 'https://example.com/app.js',
            lineNumber: 42,
            columnNumber: 17,
            disposition: 'report',
            statusCode: 200,
            referrer: 'https://example.com/',
            userAgent: 'Test Browser',
            rawPayload: $rawPayload,
        );

        self::assertSame('https://example.com/page', $report->documentUri);
        self::assertSame(
            'https://tracker.example.net/script.js',
            $report->blockedUri,
        );
        self::assertSame('script-src', $report->effectiveDirective);
        self::assertSame('script-src-elem', $report->violatedDirective);
        self::assertSame("default-src 'self'", $report->originalPolicy);
        self::assertSame('https://example.com/app.js', $report->sourceFile);
        self::assertSame(42, $report->lineNumber);
        self::assertSame(17, $report->columnNumber);
        self::assertSame('report', $report->disposition);
        self::assertSame(200, $report->statusCode);
        self::assertSame('https://example.com/', $report->referrer);
        self::assertSame('Test Browser', $report->userAgent);
        self::assertSame($rawPayload, $report->rawPayload);
    }

    #[Test]
    public function itReturnsTheBlockedHostInLowercase(): void
    {
        $report = $this->createReport(
            blockedUri: 'https://CDN.Example.COM/path/script.js',
        );

        self::assertSame('cdn.example.com', $report->blockedHost());
    }

    #[Test]
    public function itReturnsNullWhenThereIsNoBlockedUri(): void
    {
        $report = $this->createReport();

        self::assertNull($report->blockedHost());
    }

    #[Test]
    public function itReturnsNullWhenBlockedUriHasNoHost(): void
    {
        $report = $this->createReport(
            blockedUri: 'not-a-valid-host',
        );

        self::assertNull($report->blockedHost());
    }

    #[Test]
    public function itReturnsTheDocumentHostInLowercase(): void
    {
        $report = $this->createReport(
            documentUri: 'https://WWW.Example.COM/path',
        );

        self::assertSame('www.example.com', $report->documentHost());
    }

    #[Test]
    public function itReturnsNullWhenThereIsNoDocumentUri(): void
    {
        $report = $this->createReport();

        self::assertNull($report->documentHost());
    }

    #[Test]
    public function itReturnsNullWhenDocumentUriHasNoHost(): void
    {
        $report = $this->createReport(
            documentUri: 'not-a-valid-host',
        );

        self::assertNull($report->documentHost());
    }

    #[Test]
    public function itGeneratesAFingerprintFromDirectives(): void
    {
        $report = $this->createReport(
            effectiveDirective: 'script-src',
            violatedDirective: 'script-src-elem',
            documentUri: 'https://example.com/page',
            blockedUri: 'https://cdn.example.com/script.js',
            sourceFile: 'https://example.com/app.js',
        );

        $expected = sha1(implode('|', [
            'script-src',
            'script-src-elem',
            'https://example.com/page',
            'https://cdn.example.com/script.js',
            'https://example.com/app.js',
        ]));

        self::assertSame($expected, $report->fingerprint());
    }

    #[Test]
    #[DataProvider('scriptViolationProvider')]
    public function itDetectsScriptViolations(
        ?string $effectiveDirective,
        bool $expected,
    ): void {
        $report = $this->createReport(
            effectiveDirective: $effectiveDirective,
        );

        self::assertSame($expected, $report->isScriptViolation());
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function scriptViolationProvider(): iterable
    {
        yield 'script-src directive' => ['script-src', true];
        yield 'script-src-elem directive' => ['script-src-elem', true];
        yield 'style-src directive' => ['style-src', false];
        yield 'null directive' => [null, false];
    }

    #[Test]
    #[DataProvider('extensionNoiseProvider')]
    public function itDetectsExtensionNoise(
        ?string $blockedUri,
        bool $expected,
    ): void {
        $report = $this->createReport(
            blockedUri: $blockedUri,
        );

        self::assertSame($expected, $report->isExtensionNoise());
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function extensionNoiseProvider(): iterable
    {
        yield 'Chrome extension' => [
            'chrome-extension://abcdefghijkl/script.js',
            true,
        ];

        yield 'Firefox extension' => [
            'moz-extension://abcdefghijkl/script.js',
            true,
        ];

        yield 'Safari extension' => [
            'safari-extension://abcdefghijkl/script.js',
            true,
        ];

        yield 'ordinary HTTPS URI' => [
            'https://example.com/script.js',
            false,
        ];

        yield 'empty URI' => [
            '',
            false,
        ];

        yield 'null URI' => [
            null,
            false,
        ];
    }

    private function createReport(
        ?string $documentUri = null,
        ?string $blockedUri = null,
        ?string $effectiveDirective = null,
        ?string $violatedDirective = null,
        ?string $originalPolicy = null,
        ?string $sourceFile = null,
        ?int $lineNumber = null,
        ?int $columnNumber = null,
        ?string $disposition = null,
        ?int $statusCode = null,
        ?string $referrer = null,
        ?string $userAgent = null,
        array $rawPayload = [],
    ): CspReportDto {
        return new CspReportDto(
            documentUri: $documentUri,
            blockedUri: $blockedUri,
            effectiveDirective: $effectiveDirective,
            violatedDirective: $violatedDirective,
            originalPolicy: $originalPolicy,
            sourceFile: $sourceFile,
            lineNumber: $lineNumber,
            columnNumber: $columnNumber,
            disposition: $disposition,
            statusCode: $statusCode,
            referrer: $referrer,
            userAgent: $userAgent,
            rawPayload: $rawPayload,
        );
    }
}
