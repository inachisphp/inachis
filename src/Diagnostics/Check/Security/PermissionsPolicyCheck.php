<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Diagnostics\Check\Security;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class PermissionsPolicyCheck implements CheckInterface
{
    public function getId(): string
    {
        return 'permissions_policy';
    }

    public function getLabel(): string
    {
        return 'Permissions-Policy / Feature-Policy';
    }

    public function getSection(): string
    {
        return 'Security';
    }

    public function getSeverity(): string
    {
        return 'medium';
    }

    public function run(): CheckResult
    {
        $headers = array_change_key_case((array) getallheaders(), CASE_LOWER);

        $rawValue = $headers['permissions-policy'] ?? $headers['feature-policy'] ?? '(not set)';
        $value = is_string($rawValue) ? $rawValue : '(not set)';
        $status = ('(not set)' !== $value) ? 'ok' : 'warning';

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            'ok' === $status ? 'Permissions/Feature-Policy header is set.' : 'No Permissions/Feature-Policy header detected.',
            'ok' === $status ? null : 'Set Permissions-Policy or Feature-Policy header to limit browser APIs.',
            $this->getSection(),
            'medium',
        );
    }
}
