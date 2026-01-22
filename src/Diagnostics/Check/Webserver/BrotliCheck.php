<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;

final class BrotliCheck implements CheckInterface
{
    public function getId(): string { return 'brotli'; }
    public function getLabel(): string { return 'Brotli Compression'; }
    public function getSection(): string { return 'Webserver'; }

    public function run(): CheckResult
    {
        $found = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Encoding:') !== false && stripos($header, 'br') !== false) {
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
            $found ? 'Brotli compression detected.' : 'Brotli compression not detected.',
            $found ? null : 'Enable Brotli compression for better performance.',
            $this->getSection(),
            'medium'
        );
    }
}