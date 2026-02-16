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
 * Validates DMARC records
 */
final class DmarcValidator
{
    /**
     * @param list<string> $records
     * @return list<ValidationIssue>
     */
    public function validate(array $records): array
    {
        $issues = [];

        if ($records === []) {
            return [
                new ValidationIssue('dmarc', 'No DMARC record found', Severity::Error)
            ];
        }

        if (count($records) > 1) {
            $issues[] = new ValidationIssue('dmarc', 'Multiple DMARC records found', Severity::Error);
        }

        $parsed = $this->parse($records[0]);

        if (($parsed['v'] ?? null) !== 'DMARC1') {
            $issues[] = new ValidationIssue('dmarc', 'Invalid or missing DMARC version', Severity::Error);
        }

        if (!isset($parsed['p']) || !in_array($parsed['p'], ['none', 'quarantine', 'reject'], true)) {
            $issues[] = new ValidationIssue('dmarc', 'Invalid or missing policy (p)', Severity::Error);
        }

        if (!isset($parsed['sp'])) {
            $issues[] = new ValidationIssue('dmarc', 'DMARC sp not set: subdomains may inherit none', Severity::Warning);
        }

        if (isset($parsed['pct']) && (!ctype_digit($parsed['pct']) || (int)$parsed['pct'] > 100)) {
            $issues[] = new ValidationIssue('dmarc', 'pct must be between 0 and 100', Severity::Error);
        }

        foreach (['adkim', 'aspf'] as $tag) {
            if (isset($parsed[$tag])) {
                if (!in_array($parsed[$tag], ['r','s'], true)) {
                    $issues[] = new ValidationIssue('dmarc', "$tag must be r or s", Severity::Error);
                } elseif ($parsed[$tag] === 'r') {
                    $issues[] = new ValidationIssue('dmarc', "$tag is relaxed (r), consider strict (s)", Severity::Warning);
                }
            }
        }

        if (!isset($parsed['rua'])) {
            $issues[] = new ValidationIssue('dmarc', 'rua not configured (no aggregate reports)', Severity::Warning);
        } else {
            $this->validateRuaRuf($parsed['rua'], 'rua', $issues);
        }

        if (isset($parsed['ruf'])) {
            $this->validateRuaRuf($parsed['ruf'], 'ruf', $issues);
        }

        return $issues;
    }

    /**
     * Parse DMARC record
     * @param string $record
     * @return array
     */
    private function parse(string $record): array
    {
        $result = [];
        foreach (explode(';', $record) as $part) {
            $part = trim($part);
            if (str_contains($part, '=')) {
                [$k, $v] = explode('=', $part, 2);
                $result[strtolower(trim($k))] = trim($v);
            }
        }
        return $result;
    }

    /**
     * Validate RUA/RUF records
     * @param string $rua
     * @param string $type
     * @param array $issues
     * @return void
     */
    private function validateRuaRuf(string $rua, string $type, array &$issues): void
    {
        $addresses = explode(',', $rua);
        foreach ($addresses as $address) {
            $address = trim(str_replace('mailto:', '', $address));
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $issues[] = new ValidationIssue('dmarc', "Invalid $type email: $address", Severity::Error);
            }
        }
    }
}