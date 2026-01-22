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

final class CacheHeadersCheck implements CheckInterface
{
    public function getId(): string { return 'cache_headers'; }
    public function getLabel(): string { return 'Cache / Expires Headers'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Cache-Control:') === 0 || stripos($header, 'Expires:') === 0) {
                $found = true;
                break;
            }
        }

        $status = $found ? 'ok' : 'warning';
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            $found ? 'Cache headers detected.' : 'No cache headers detected.',
            $found ? null : 'Configure caching via Cache-Control or Expires headers.',
            $this->getSection(),
            'medium'
        );
    }
}
