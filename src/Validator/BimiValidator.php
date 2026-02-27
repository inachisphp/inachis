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
 * Validates BIMI records
 */
final class BimiValidator
{
    /**
     * Validate BIMI records
     * @param list<array{target: string, priority: int}> $records
     * @return list<ValidationIssue>
     */
    public function validate(array $records, string $dmarc): array
    {
        $issues = [];

        if (str_contains(strtolower($dmarc), 'p=reject')) {
            if (empty($records)) {
                $issues[] = new ValidationIssue('bimi', 'No BIMI record found (required when DMARC is reject)', Severity::Warning);
            } else {
                foreach ($records as $txt) {
                    $value = $txt['txt'] ?? '';
                    if (!preg_match('/^v=BIMI1;.+?;$/i', $value)) {
                        $issues[] = new ValidationIssue('bimi', "Invalid BIMI record format: $value", Severity::Error);
                    }
                }
            }
        }

        return $issues;
    }
}