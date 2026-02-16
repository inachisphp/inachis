<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Domain;

/**
 * DNS resolver interface
 */
interface DnsResolverInterface
{
    /**
     * Get DNS records for a host
     *
     * @param string $host
     * @param int $type
     * @return array
     */
    public function getRecords(string $host, int $type): array;
}