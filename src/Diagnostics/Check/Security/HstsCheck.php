<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class HstsCheck implements CheckInterface
{
    public function getId(): string { return 'hsts'; }
    public function getLabel(): string { return 'HSTS Header'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Strict-Transport-Security:') === 0) {
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
            $found ? 'HSTS header is present.' : 'HSTS header is missing.',
            $found ? null : 'Add HSTS header to enforce HTTPS.',
            $this->getSection(),
            'high'
        );
    }
}
