<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Domain;

/**
 * Domain DNS report
 */
final class DomainDnsReport
{
    /**
     * @param string $domain
     * @param list<array<string, mixed>> $dkimRecords
     * @param list<array<string, mixed>> $mxRecords
     * @param list<string> $spfRecords
     * @param list<string> $dmarcRecords
     * @param list<array<string, mixed>> $bimiRecord
     * @param list<array<string, mixed>> $tlsRptRecords
     * @param list<array<string, mixed>> $caaRecords
     * @param list<ValidationIssue> $issues
     */
    public function __construct(
        public string $domain,
        public array $dkimRecords,
        public array $mxRecords,
        public array $spfRecords,
        public array $dmarcRecords,
        public array $bimiRecord,
        public array $tlsRptRecords,
        public array $caaRecords,
        public array $issues,
    ) {}

    /**
     * Check if the domain has any issues
     *
     * @return bool
     */
    public function hasIssues(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === Severity::Error) {
                return true;
            }
        }

        return false;
    }
}