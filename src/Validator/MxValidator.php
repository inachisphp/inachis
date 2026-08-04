<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Validator;

use Inachis\Model\Domain\Severity;
use Inachis\Model\Domain\ValidationIssue;

/**
 * Validates MX records.
 *
 * @phpstan-import-type DnsMxRecord from \Inachis\Service\System\Domain\DnsResolverInterface
 */
final class MxValidator
{
    /**
     * Validate MX records.
     *
     * @param list<DnsMxRecord> $records
     *
     * @return list<ValidationIssue>
     */
    public function validate(array $records): array
    {
        $issues = [];

        if ([] === $records) {
            return [
                new ValidationIssue('mx', 'No MX records found', Severity::Error),
            ];
        }

        if (1 === count($records)) {
            $issues[] = new ValidationIssue('mx', 'Only one MX record configured (no redundancy)', Severity::Warning);
        }

        foreach ($records as $mx) {
            $target = $mx['target'] ?? '';
            if ('.' === $target) {
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
