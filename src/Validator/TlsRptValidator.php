<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Validator;

use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

/**
 * Validates TLS-RPT records
 */
final class TlsRptValidator
{
    /**
     * Validate TLS-RPT records
     * @param list<array{target: string, priority: int}> $records
     * @return list<ValidationIssue>
     */
    public function validate(array $records): array
    {
        $issues = [];

        foreach ($records as $txt) {
            $value = $txt['txt'] ?? '';
            if (!str_starts_with($value, 'v=TLSRPTv1')) {
                $issues[] = new ValidationIssue('tls-rpt', "Invalid TLS-RPT record format: $value", Severity::Error);
            }
        }

        return $issues;
    }
}