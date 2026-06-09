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
    /** @var array<string> */
    private array $sensitivePaths = [
        'var',
        'config',
        'bin',
    ];

    /**
     * Returns the ID of the checl
     *
     * @return string
     */
    public function getId(): string { return 'webroot_containment'; }

    /**
     * Returns the friendly name of the check
     *
     * @return string
     */
    public function getLabel(): string { return 'Webroot Containment / Directory Security'; }

    /**
     * Returns the section this check displays under
     *
     * @return string
     */
    public function getSection(): string { return 'Security'; }

    /**
     * Returns the severity of the check
     * 
     * @return string
     */
    public function getSeverity(): string { return 'high'; }

    /**
     * Runs the check
     *
     * @return CheckResult
     */
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