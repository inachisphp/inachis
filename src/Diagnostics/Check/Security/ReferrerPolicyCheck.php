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

final class ReferrerPolicyCheck implements CheckInterface
{
    public function getId(): string { return 'referrer_policy'; }
    public function getLabel(): string { return 'Referrer-Policy'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Referrer-Policy:') === 0) {
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
            $found ? 'Referrer-Policy header is present.' : 'Referrer-Policy header is missing.',
            $found ? null : 'Add Referrer-Policy header to control referrer information.',
            $this->getSection(),
            'high'
        );
    }
}
