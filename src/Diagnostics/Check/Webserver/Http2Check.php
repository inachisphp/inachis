<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class Http2Check implements CheckInterface
{
    public function getId(): string { return 'http2'; }
    public function getLabel(): string { return 'HTTP/2 Support'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
        $status = str_contains($protocol, 'HTTP/2') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $protocol,
            $status === 'ok' ? "HTTP/2 detected ($protocol)." : "HTTP/2 not detected.",
            $status === 'ok' ? null : 'Enable HTTP/2 in your webserver for performance.',
            $this->getSection(),
            'medium'
        );
    }
}
