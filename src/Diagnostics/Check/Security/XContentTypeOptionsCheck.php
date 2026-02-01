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

final class XContentTypeOptionsCheck implements CheckInterface
{
    public function getId(): string { return 'x_content_type_options'; }
    public function getLabel(): string { return 'X-Content-Type-Options'; }
    public function getSection(): string { return 'Security'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'X-Content-Type-Options:') === 0) {
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
            $found ? 'Header is present.' : 'Header is missing.',
            $found ? null : 'Add X-Content-Type-Options: nosniff for security.',
            $this->getSection(),
            'high'
        );
    }
}
