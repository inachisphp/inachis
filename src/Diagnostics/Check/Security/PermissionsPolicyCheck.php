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

final class PermissionsPolicyCheck implements CheckInterface
{
    public function getId(): string { return 'permissions_policy'; }
    public function getLabel(): string { return 'Permissions-Policy / Feature-Policy'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'medium'; }

    public function run(): CheckResult
    {
        $headers = getallheaders();
        $value = $headers['Permissions-Policy'] ?? $headers['Feature-Policy'] ?? '(not set)';
        $status = ($value !== '(not set)') ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Permissions/Feature-Policy header is set.' : 'No Permissions/Feature-Policy header detected.',
            $status === 'ok' ? null : 'Set Permissions-Policy or Feature-Policy header to limit browser APIs.',
            $this->getSection(),
            'medium'
        );
    }
}