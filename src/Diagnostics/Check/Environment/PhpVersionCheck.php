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

final class PhpVersionCheck implements CheckInterface
{
    private string $recommended = '8.3';

    public function __construct(private string $currentVersion = PHP_VERSION) {}

    public function getId(): string { return 'php_version'; }
    public function getLabel(): string { return 'PHP Version'; }
    public function getSection(): string { return 'Environment'; }

    public function run(): CheckResult
    {
        $version = $this->currentVersion;
        $status = version_compare($version, $this->recommended, '>=') ? 'ok' : 'warning';
        $details = "Detected PHP version: $version. Recommended >= {$this->recommended}";

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $version,
            $details,
            $status === 'ok' ? null : "Upgrade PHP to {$this->recommended} or later.",
            $this->getSection(),
            'high'
        );
    }
}
