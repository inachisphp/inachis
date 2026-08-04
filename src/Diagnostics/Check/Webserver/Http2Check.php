<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class Http2Check implements CheckInterface
{
    public function getId(): string
    {
        return 'http2';
    }

    public function getLabel(): string
    {
        return 'HTTP/2 Support';
    }

    public function getSection(): string
    {
        return 'Webserver';
    }

    public function run(): CheckResult
    {
        /** @var string */
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        $status = str_contains($protocol, 'HTTP/2') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $protocol,
            'ok' === $status ? "HTTP/2 detected ($protocol)." : 'HTTP/2 not detected.',
            'ok' === $status ? null : 'Enable HTTP/2 in your webserver for performance.',
            $this->getSection(),
            'medium',
        );
    }
}
