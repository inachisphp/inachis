<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Domain;

/**
 * DNS resolver interface
 *
 * @phpstan-type DnsMxRecord array{host: string, class: string, ttl: int, type: 'MX', pri: int, target?: string, priority?: string}
 * @phpstan-type DnsTxtRecord array{host: string, class: string, ttl: int, type: 'TXT', txt?: string, entries: list<string>}
 * @phpstan-type DnsCaaRecord array{host: string, class: string, ttl: int, type: 'CAA', flags: int, tag: string, value?: string}
 * @phpstan-type DnsCnameRecord array{host: string, class: string, ttl: int, type: 'CNAME', target?: string}
 * @phpstan-type DnsRecord DnsMxRecord|DnsTxtRecord|DnsCaaRecord|DnsCnameRecord
 * @phpstan-type DnsEntries list<DnsRecord>
 */
interface DnsResolverInterface
{
    /**
     * Get DNS records for a host
     *
     * @param string $host
     * @param int $type
     * @return DnsEntries
     */
    public function getRecords(string $host, int $type): array;
}