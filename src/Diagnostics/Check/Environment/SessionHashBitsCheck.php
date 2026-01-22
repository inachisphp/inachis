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

final class SessionHashBitsCheck implements CheckInterface
{
    public function getId(): string { return 'session_hash_bits_per_character'; }
    public function getLabel(): string { return 'session.hash_bits_per_character'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $value = (int) ini_get('session.hash_bits_per_character');

        if ($value >= 5) {
            $status = 'ok';
            $severity = 'low';
        } else {
            $status = 'warning';
            $severity = 'medium';
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Session hash strength is sufficient.' : 'Session hash entropy is low; consider increasing hash_bits_per_character.',
            $status !== 'ok' ? 'Set session.hash_bits_per_character >= 5 in php.ini for stronger session IDs.' : null,
            $this->getSection(),
            $severity
        );
    }
}