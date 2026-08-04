<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

/**
 * DTO model for CSP Reports.
 */
final readonly class CspReportDto
{
    /**
     * Constructor for CspReportDto.
     *
     * @param array<string,int|string|array<string, int|string>> $rawPayload
     */
    public function __construct(
        public ?string $documentUri,
        public ?string $blockedUri,
        public ?string $effectiveDirective,
        public ?string $violatedDirective,
        public ?string $originalPolicy,
        public ?string $sourceFile,
        public ?int $lineNumber,
        public ?int $columnNumber,
        public ?string $disposition,
        public ?int $statusCode,
        public ?string $referrer,
        public ?string $userAgent,
        public array $rawPayload,
    ) {
    }

    /**
     * Returns a string for the hostname that is blocked if set.
     */
    public function blockedHost(): ?string
    {
        if (!$this->blockedUri) {
            return null;
        }
        $host = parse_url($this->blockedUri, PHP_URL_HOST);

        return \is_string($host)
            ? strtolower($host)
            : null;
    }

    /**
     * Returns a string for the document host if set.
     */
    public function documentHost(): ?string
    {
        if (!$this->documentUri) {
            return null;
        }
        $host = parse_url($this->documentUri, PHP_URL_HOST);

        return \is_string($host)
            ? strtolower($host)
            : null;
    }

    /**
     * Generate a SHA-1 fingerprint from directives.
     */
    public function fingerprint(): string
    {
        return sha1(implode('|', [
            $this->effectiveDirective,
            $this->violatedDirective,
            $this->documentUri,
            $this->blockedUri,
            $this->sourceFile,
        ]));
    }

    /**
     * Checks directive to see if this is script-src.
     */
    public function isScriptViolation(): bool
    {
        return str_contains(
            (string) $this->effectiveDirective,
            'script-src',
        );
    }

    /**
     * Checks if the source of report is 'noise'.
     */
    public function isExtensionNoise(): bool
    {
        if (!$this->blockedUri) {
            return false;
        }

        return str_starts_with($this->blockedUri, 'chrome-extension://')
            || str_starts_with($this->blockedUri, 'moz-extension://')
            || str_starts_with($this->blockedUri, 'safari-extension://');
    }
}
