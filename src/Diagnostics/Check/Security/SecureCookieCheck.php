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

final class SecureCookieCheck implements CheckInterface
{
    public function getId(): string { return 'secure_cookie'; }
    public function getLabel(): string { return 'Secure & HttpOnly Cookies'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $status = 'ok';
        $details = [];

        foreach ($_COOKIE as $name => $value) {
            $params = session_get_cookie_params();
            if (!($params['secure'] ?? false) || !($params['httponly'] ?? false)) {
                $details[] = "$name: missing secure or HttpOnly";
                $status = 'warning';
            } else {
                $details[] = "$name: secure & HttpOnly";
            }
        }

        if (empty($_COOKIE)) {
            $details[] = 'No cookies detected.';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            implode("\n", $details),
            $status === 'ok' ? null : 'Configure cookies with Secure and HttpOnly flags.',
            $this->getSection(),
            'high'
        );
    }
}