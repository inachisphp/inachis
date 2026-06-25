<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

final readonly class CspReportDto
{
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

    public function isScriptViolation(): bool
    {
        return str_contains(
            (string) $this->effectiveDirective,
            'script-src'
        );
    }

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
