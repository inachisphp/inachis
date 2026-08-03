<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class KeepAliveCheck implements CheckInterface
{
    public function getId(): string { return 'keep_alive'; }
    public function getLabel(): string { return 'HTTP Keep-Alive'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Connection:') === 0 && stripos($header, 'keep-alive') !== false) {
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
            $found ? 'Keep-Alive detected.' : 'Keep-Alive not detected.',
            $found ? null : 'Enable Keep-Alive for better connection performance.',
            $this->getSection(),
            'medium'
        );
    }
}