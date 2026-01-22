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

final class ContentSecurityPolicyCheck implements CheckInterface
{
    public function getId(): string { return 'csp'; }
    public function getLabel(): string { return 'Content-Security-Policy'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Security-Policy:') === 0) {
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
            $found ? 'CSP header is present.' : 'CSP header is missing.',
            $found ? null : 'Add a Content-Security-Policy header to reduce XSS risks.',
            $this->getSection(),
            'high'
        );
    }
}