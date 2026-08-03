<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class SessionHandlerCheck implements CheckInterface
{
    public function getId(): string { return 'session_handler'; }
    public function getLabel(): string { return 'Session Handler'; }
    public function getSection(): string { return 'Performance'; }

    public function run(): CheckResult
    {
        $handler = ini_get('session.save_handler') ?: 'unknown';
        $status = in_array($handler, ['files', 'redis', 'memcached']) ? 'ok' : 'warning';
        $details = "session.save_handler: $handler";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $handler,
            $details,
            $status === 'ok' ? null : 'Use files, Redis, or Memcached for session storage.',
            $this->getSection(),
            'medium'
        );
    }
}
