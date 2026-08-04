<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Inachis\Enum\System\CspSeverity;
use Inachis\Model\System\CspReportDto;

/**
 * Determines the overall severity of the reported item.
 */
final class CspSeverityResolver
{
    /**
     * Resolves the DTO severity to the enum.
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
