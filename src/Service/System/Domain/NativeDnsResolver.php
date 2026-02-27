<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Domain;

/**
 * Native DNS resolver
 */
final class NativeDnsResolver implements DnsResolverInterface
{
    /**
     * @var int Number of times to retry DNS lookup
     */
    private int $retryCount = 2;

    /**
     * @var int Delay between retries in milliseconds
     */
    private int $retryDelay = 100;

    /**
     * @param string $host
     * @param int $type
     * @return array
     */
    public function getRecords(string $host, int $type): array
    {
        $records = [];
        $attempts = 0;

        while ($attempts <= $this->retryCount) {
            $records = @dns_get_record($host, $type) ?: [];
            if (!empty($records)) {
                break;
            }

            // Retry on failure
            $attempts++;
            if ($attempts <= $this->retryCount) {
                usleep($this->retryDelay * 1000);
            }
        }

        // Handle CNAME flattening for TXT lookups (DKIM/SPF/BIMI)
        if ($type === DNS_TXT) {
            $records = $this->flattenCnameTxt($host, $records);
        }

        return $records;
    }

    /**
     * Flattens CNAME records for TXT lookups
     *
     * @param string $host
     * @param array $records
     * @return array
     */
    private function flattenCnameTxt(string $host, array $records): array
    {
        foreach ($records as $rec) {
            if ($rec['type'] === 'CNAME' && isset($rec['target'])) {
                $records = array_merge($records, $this->getRecords($rec['target'], DNS_TXT));
            }
        }
        return $records;
    }
}