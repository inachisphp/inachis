<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Csp;

use Inachis\Model\System\CspReportDto;

/**
 * Normalises the report into a DTO for portability
 */
final class CspReportDtoFactory
{
    /**
     * Turns CSP 1.0 reports into a DTO
     *
     * @param array $report
     * @param string|null $userAgent
     * @param string|null $referrer
     * @return CspReportDto
     */
    public function fromLegacyReport(
        array $report,
        ?string $userAgent = null,
        ?string $referrer = null,
    ): CspReportDto {
        return new CspReportDto(
            documentUri: $report['document-uri'] ?? null,
            blockedUri: $report['blocked-uri'] ?? null,
            effectiveDirective: $report['effective-directive'] ?? null,
            violatedDirective: $report['violated-directive'] ?? null,
            originalPolicy: $report['original-policy'] ?? null,
            sourceFile: $report['source-file'] ?? null,
            lineNumber: $report['line-number'] ?? null,
            columnNumber: $report['column-number'] ?? null,
            disposition: $report['disposition'] ?? null,
            statusCode: $report['status-code'] ?? null,
            referrer: $referrer,
            userAgent: $userAgent,
            rawPayload: $report,
        );
    }

    /**
     * Turns CSP 2.0 reports into a DTO
     *
     * @param array $report
     * @param string|null $userAgent
     * @param string|null $referrer
     * @return CspReportDto
     */
    public function fromReportingApi(
        array $report,
        ?string $userAgent = null,
        ?string $referrer = null,
    ): CspReportDto {
        $body = $report['body'] ?? [];

        return new CspReportDto(
            documentUri: $body['documentURL'] ?? null,
            blockedUri: $body['blockedURL'] ?? null,
            effectiveDirective: $body['effectiveDirective'] ?? null,
            violatedDirective: $body['violatedDirective'] ?? null,
            originalPolicy: $body['originalPolicy'] ?? null,
            sourceFile: $body['sourceFile'] ?? null,
            lineNumber: $body['lineNumber'] ?? null,
            columnNumber: $body['columnNumber'] ?? null,
            disposition: $body['disposition'] ?? null,
            statusCode: null,
            referrer: $referrer,
            userAgent: $userAgent,
            rawPayload: $report,
        );
    }
}
