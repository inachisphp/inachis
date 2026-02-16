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
 * Validates MX records
 */
final class MxValidator
{
    /**
     * Validate MX records
     * @param list<array{target: string, priority: int}> $records
     * @return list<ValidationIssue>
     */
    public function validate(array $records): array
    {
        $issues = [];

        if ($records === []) {
            return [
                new ValidationIssue('mx', 'No MX records found', Severity::Error)
            ];
        }

        if (count($records) === 1) {
            $issues[] = new ValidationIssue('mx', 'Only one MX record configured (no redundancy)', Severity::Warning);
        }

        foreach ($records as $mx) {
            $target = $mx['target'] ?? '';
            if ($target === '.') {
                $issues[] = new ValidationIssue('mx', 'Null MX configured (domain does not accept mail)', Severity::Info);
            }

            if (gethostbyname($target) === $target) {
                $issues[] = new ValidationIssue('mx', "MX host '{$target}' does not resolve", Severity::Warning);
            }
        }

        $priorities = array_count_values(array_column($records, 'priority'));
        foreach ($priorities as $priority => $count) {
            if ($count > 1) {
                $issues[] = new ValidationIssue('mx', "Multiple MX records share priority {$priority}", Severity::Info);
            }
        }

        return $issues;
    }
}