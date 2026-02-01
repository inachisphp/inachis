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

final class PhpExtensionsCheck implements CheckInterface
{
    private array $required = ['pdo', 'mbstring', 'openssl'];

    public function getId(): string { return 'php_extensions'; }
    public function getLabel(): string { return 'PHP Extensions'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $details = [];
        $status = 'ok';

        foreach ($this->required as $ext) {
            if (extension_loaded($ext)) {
                $details[] = "$ext: enabled";
            } else {
                $details[] = "$ext: missing";
                $status = 'warning';
            }
        }

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            null,
            implode("\n", $details),
            $status === 'ok' ? null : 'Install missing PHP extensions.',
            $this->getSection(),
            'high'
        );
    }
}
