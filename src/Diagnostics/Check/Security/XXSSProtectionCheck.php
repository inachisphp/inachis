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

final class XXSSProtectionCheck implements CheckInterface
{
    public function getId(): string { return 'x_xss_protection'; }
    public function getLabel(): string { return 'X-XSS-Protection'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'X-XSS-Protection:') === 0) {
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
            $found ? 'X-XSS-Protection header is present.' : 'Header missing.',
            $found ? null : 'Add X-XSS-Protection header for legacy browser XSS mitigation.',
            $this->getSection(),
            'medium'
        );
    }
}