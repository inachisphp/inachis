<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Validator;

use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

/**
 * Validates CAA records
 * 
 * @phpstan-import-type DnsCaaRecord from \Inachis\Service\System\Domain\DnsResolverInterface
 */
final class CaaValidator
{
    /**
     * Validate CAA records
     * @param list<DnsCaaRecord> $records
     * @return list<ValidationIssue>
     */
    public function validate(array $records): array
    {
        $issues = [];

        foreach ($records as $record) {
            if (!isset($record['value'])) {
                $issues[] = new ValidationIssue('caa', 'Malformed CAA record', Severity::Error);
            }
        }

        return $issues;
    }
}