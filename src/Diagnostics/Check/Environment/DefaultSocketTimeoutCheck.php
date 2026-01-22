<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Environment;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class DefaultSocketTimeoutCheck implements CheckInterface
{
    public function getId(): string { return 'default_socket_timeout'; }
    public function getLabel(): string { return 'default_socket_timeout'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('default_socket_timeout'); // in seconds

        if ($value === 0) {
            $status = 'error';
            $severity = 'high';
        } elseif ($value > 30) {
            $status = 'warning';
            $severity = 'medium';
        } else {
            $status = 'ok';
            $severity = 'low';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value . ' seconds',
            $status === 'ok' ? 'default_socket_timeout is within recommended range.' : 'Value may cause blocking during network operations.',
            $status !== 'ok' ? 'Consider setting default_socket_timeout to 10–15 seconds for better reliability.' : null,
            $this->getSection(),
            $severity
        );
    }
}