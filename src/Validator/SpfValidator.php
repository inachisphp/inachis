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
 * SPF validator
 */
final class SpfValidator
{
    /**
     * Validate SPF records
     *
     * @param list<string> $spfRecords
     * @return list<ValidationIssue>
     */
    public function validate(array $spfRecords): array
    {
        $issues = [];

        if ($spfRecords === []) {
            $issues[] = new ValidationIssue('spf', 'No SPF record found', Severity::Error);
            return $issues;
        }

        if (count($spfRecords) > 1) {
            $issues[] = new ValidationIssue('spf', 'Multiple SPF records found (RFC 7208 violation)', Severity::Error);
        }

        $spf = $spfRecords[0];
        if (strlen($spf) > 255) {
            $issues[] = new ValidationIssue('spf', 'SPF record exceeds 255 characters (may break DNS)', Severity::Warning);
        }

        $spf = strtolower(trim($spf));
        if (!str_starts_with($spf, 'v=spf1')) {
            $issues[] = new ValidationIssue('spf', 'SPF must start with v=spf1', Severity::Error);
        }
        if (str_contains($spf, '+all')) {
            $issues[] = new ValidationIssue('spf', 'SPF contains +all (allows anyone to send mail)', Severity::Error);
        }
        if (str_contains($spf, '~all')) {
            $issues[] = new ValidationIssue('spf', 'SPF uses softfail (~all) instead of -all', Severity::Warning);
        }
        if (str_contains($spf, 'ptr')) {
            $issues[] = new ValidationIssue('spf', 'SPF uses deprecated ptr mechanism', Severity::Warning);
        }

        foreach (['exists:', 'redirect='] as $mechanism) {
            if (str_contains($spf, $mechanism)) {
                $issues[] = new ValidationIssue('spf', "SPF uses deprecated {$mechanism} mechanism", Severity::Warning);
            }
        }

        $lookupCount = $this->countLookups($spf);
        if ($lookupCount > 10) {
            $issues[] = new ValidationIssue('spf', "SPF exceeds 10 DNS lookups ($lookupCount found)", Severity::Error);
        }
        return $issues;
    }

	/**
	 * Count DNS lookups in SPF record
	 *
	 * @param string $spf
	 * @return int
	 */
	private function countLookups(string $spf): int
	{
		preg_match_all('/include:|a:|mx:|exists:|redirect=/', $spf, $matches);
        return count($matches[0]);
	}
}