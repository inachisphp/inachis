<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Csp;

use Inachis\Model\System\CspReportDto;
use Inachis\Enum\System\CspSeverity;

/**
 * Determines the overall severity of the reported item
 */
final class CspSeverityResolver
{
    /**
     * Resolves the DTO severity to the enum
     *
     * @param CspReportDto $dto
     * @return CspSeverity
     */
    public function resolve(
        CspReportDto $dto,
    ): CspSeverity {
        $directive = $dto->effectiveDirective ?? '';

        return match (true) {
            str_contains($directive, 'script-src') => CspSeverity::Critical,
            str_contains($directive, 'connect-src') => CspSeverity::High,
            str_contains($directive, 'frame-src') => CspSeverity::High,
            str_contains($directive, 'style-src') => CspSeverity::Medium,
            str_contains($directive, 'img-src') => CspSeverity::Low,
            default => CspSeverity::Info,
        };
    }
}
