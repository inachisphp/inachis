<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class CacheHeadersCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'cache_headers';
    }

    public function getLabel(): string
    {
        return 'Cache / Expires Headers';
    }

    public function getSection(): string
    {
        return 'Webserver';
    }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (0 === stripos($header, 'Cache-Control:') || 0 === stripos($header, 'Expires:')) {
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
            'medium',
        );
    }
}
