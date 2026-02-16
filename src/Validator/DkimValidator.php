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
 * Validates DKIM records
 */
final class DkimValidator
{
    /**
     * Validates DKIM records
     * 
     * @param list<string> $records
     * @param string $selector
     * @return list<ValidationIssue>
     */
    public function validate(array $records, string $selector): array
    {
        $issues = [];

        if (empty($records)) {
            $issues[] = new ValidationIssue('dkim', "No DKIM record found for selector '{$selector}'", Severity::Warning);
            return $issues;
        }

        $seenSelectors = [];

        foreach ($records as $record) {
            $txt = trim($record['txt'] ?? '');
            $lower = strtolower($txt);

            if (!str_starts_with($lower, 'v=dkim1')) {
                $issues[] = new ValidationIssue(
                    'dkim',
                    "DKIM record for selector '{$selector}' missing or invalid version",
                    Severity::Error
                );
                continue;
            }

            if (!str_contains($lower, 'p=')) {
                $issues[] = new ValidationIssue(
                    'dkim',
                    "DKIM record for selector '{$selector}' missing public key (p=)",
                    Severity::Error
                );
            } else {
                $pubKey = $this->extractPublicKey($txt);
                if ($pubKey !== null) {
                    $keyLength = $this->getDkimKeyLength($pubKey);
                    if ($keyLength < 1024) {
                        $issues[] = new ValidationIssue(
                            'dkim',
                            "DKIM key for selector '{$selector}' is too short ({$keyLength} bits)",
                            Severity::Warning
                        );
                    }
                }
            }

            if (!str_contains($lower, 'k=')) {
                $issues[] = new ValidationIssue(
                    'dkim',
                    "DKIM record for selector '{$selector}' missing key type (k=)",
                    Severity::Warning
                );
            }

            if (preg_match('/p=([A-Za-z0-9+\/=]+);?/', $txt, $matches)) {
                $keyData = $matches[1];
                if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $keyData)) {
                    $issues[] = new ValidationIssue(
                        'dkim',
                        "DKIM public key contains invalid characters for selector '{$selector}'",
                        Severity::Error
                    );
                }
            }

            if (in_array($selector, $seenSelectors, true)) {
                $issues[] = new ValidationIssue(
                    'dkim',
                    "Duplicate DKIM selector '{$selector}' detected",
                    Severity::Warning
                );
            }
            $seenSelectors[] = $selector;
        }

        return $issues;
    }

    /**
     * Extract public key from DKIM record
     * 
     * @param string $txt
     * @return string|null
     */
    private function extractPublicKey(string $txt): ?string
    {
        if (preg_match('/p=([A-Za-z0-9+\/=]+)/', $txt, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get DKIM key length
     * 
     * @param string $pubKey
     * @return int
     */
    private function getDkimKeyLength(string $pubKey): int
    {
        $key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($pubKey, 64, "\n") . "-----END PUBLIC KEY-----";
        $res = openssl_pkey_get_public($key);
        if ($res === false) return 0;
        $details = openssl_pkey_get_details($res);
        return $details['bits'] ?? 0;
    }
}