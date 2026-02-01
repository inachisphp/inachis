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

final class WebrootContainmentCheck implements CheckInterface
{
    private array $sensitivePaths = [
        'var',
        'config',
        'bin',
    ];

    public function getId(): string { return 'webroot_containment'; }
    public function getLabel(): string { return 'Webroot Containment / Directory Security'; }
    public function getSection(): string { return 'Security'; }
    public function getSeverity(): string { return 'high'; }

    public function run(): CheckResult
    {
        $issues = [];
        foreach ($this->sensitivePaths as $path) {
            if (file_exists('public/' . $path) || is_link('public/' . $path)) {
                $issues[] = $path;
            }
        }

        $status = empty($issues) ? 'ok' : 'warning';
        $value = empty($issues) ? 'Sensitive directories are outside webroot.' : implode(', ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $status === 'ok' ? 'Webroot containment is correct.' : 'Sensitive directories inside webroot!',
            $status === 'ok' ? null : 'Ensure only public/ is web-accessible; move other directories outside.',
            $this->getSection(),
            'high'
        );
    }
}