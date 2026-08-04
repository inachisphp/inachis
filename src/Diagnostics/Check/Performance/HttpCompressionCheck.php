<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class HttpCompressionCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'http_compression';
    }

    public function getLabel(): string
    {
        return 'HTTP Compression';
    }

    public function getSection(): string
    {
        return 'Performance';
    }

    public function run(): CheckResult
    {
        foreach (headers_list() as $header) {
            if (0 === stripos($header, 'Content-Encoding:')) {
                $encoding = trim(substr($header, 17));

                return new CheckResult(
                    $this->getId(),
                    $this->getLabel(),
                    'ok',
                    $encoding,
                    'Responses are being compressed before being sent to clients.',
                    null,
                    $this->getSection(),
                    'high',
                );
            }
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            'warning',
            null,
            'No compression detected in the HTTP response.',
            'Enable gzip or brotli compression at the web server or CDN level.',
            $this->getSection(),
            'high',
        );
    }
}
