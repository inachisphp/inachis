<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\System\Domain;

use InvalidArgumentException;
use Inachis\Model\Domain\DomainDnsReport;
use Inachis\Validator\BimiValidator;
use Inachis\Validator\DkimValidator;
use Inachis\Validator\DmarcValidator;
use Inachis\Validator\MxValidator;
use Inachis\Validator\SpfValidator;
use Inachis\Validator\TlsRptValidator;
use Inachis\Validator\CaaValidator;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Model\Domain\Severity;

/**
 * Analyses the email settings of a domain
 */
final class DomainEmailAnalyser
{
    /**
     * @param DnsResolverInterface $dns
     * @param DkimValidator $dkimValidator
     * @param SpfValidator $spfValidator
     * @param DmarcValidator $dmarcValidator
     * @param MxValidator $mxValidator
     * @param BimiValidator $bimiValidator
     * @param TlsRptValidator $tlsRptValidator
     * @param CaaValidator $caaValidator
     */
    public function __construct(
        private DnsResolverInterface $dns,
        private DkimValidator $dkimValidator,
        private SpfValidator $spfValidator,
        private DmarcValidator $dmarcValidator,
        private MxValidator $mxValidator,
        private BimiValidator $bimiValidator,
        private TlsRptValidator $tlsRptValidator,
        private CaaValidator $caaValidator,
    ) {}

    /**
     * Analyses the email settings of a domain
     *
     * @param string $domain
     * @param string $serverIp
     * @param string $selector
     * @return DomainDnsReport
     */
    public function analyse(string $domain, ?string $serverIp = '', string $selector = 'default'): DomainDnsReport
    {
        $domain = $this->normaliseDomain($domain);
        $this->assertValidDomain($domain);

        $txtRecords = $this->dns->getRecords($domain, DNS_TXT);
        $mxRecords = $this->dns->getRecords($domain, DNS_MX);
        $dmarcTxt = $this->dns->getRecords('_dmarc.' . $domain, DNS_TXT);
        $dkimHost = sprintf('%s._domainkey.%s', $selector, $domain);
        $dkimTxt = $this->dns->getRecords($dkimHost, DNS_TXT);
        $bimiRecord = $this->dns->getRecords("_bimi.$domain", DNS_TXT);
        $tlsRptRecords = $this->dns->getRecords("_smtp._tls.$domain", DNS_TXT);
        $caaRecords = $this->dns->getRecords($domain, DNS_CAA);

        $spfRecords = $this->extractSpf($txtRecords);
        $dmarcRecords = $this->extractDmarc($dmarcTxt);
        $dkimRecords = $this->extractDkim($dkimTxt);

        $issues = [
            ...$this->spfValidator->validate($spfRecords),
            ...$this->checkSpfMechanisms($spfRecords),
            ...$this->dmarcValidator->validate($dmarcRecords),
            ...$this->mxValidator->validate($mxRecords),
            ...$this->dkimValidator->validate($dkimRecords, $selector),
            ...$this->bimiValidator->validate($bimiRecord, $dmarcRecords[0] ?? ''),
            ...$this->tlsRptValidator->validate($tlsRptRecords),
            ...$this->caaValidator->validate($caaRecords),
        ];

        if (!empty($serverIp) && !empty($spfRecords)) {
            $authorized = $this->isIpAuthorized($serverIp, $spfRecords[0] ?? '', $issues);
            if (!$authorized) {
                $issues[] = new ValidationIssue(
                    'spf',
                    "Server IP {$serverIp} is NOT authorized in SPF record",
                    Severity::Error
                );
            }
        }

        foreach ($mxRecords as $mx) {
            $host = $mx['target'] ?? null;
            if ($host) {
                try {
                    $valid = $this->validateMxTls($host);
                    if (!$valid) {
                        $issues[] = new ValidationIssue('mx', "MX host {$host} TLS certificate invalid", Severity::Warning);
                    }
                } catch (\Exception $e) {
                    $issues[] = new ValidationIssue('mx', "MX host {$host} TLS check failed: " . $e->getMessage(), Severity::Warning);
                }
            }
        }

        return new DomainDnsReport(
            $domain,
            $dkimRecords,
            $mxRecords,
            $spfRecords,
            $dmarcRecords,
            $bimiRecord,
            $tlsRptRecords,
            $caaRecords,
            $issues
        );
    }

    /**
     * Asserts that the domain is valid
     *
     * @param string $domain
     */
    private function assertValidDomain(string $domain): void
    {
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException('Invalid domain.');
        }
    }

    /**
     * Check full SPF mechanisms for deprecated/unsupported issues
     *
     * @param list<string> $spfRecords
     * @return list<ValidationIssue>
     */
    private function checkSpfMechanisms(array $spfRecords): array
    {
        $issues = [];

        foreach ($spfRecords as $spf) {
            $spf = strtolower($spf);

            // Parse mechanisms
            preg_match_all('/(?:(?:\+|\-|\~|\?)?(ip4|ip6|include|a|mx|ptr|exists|redirect)(?::([^\s]+))?)/', $spf, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                [$full, $mechanism, $value] = $match + [null, null, null];

                switch ($mechanism) {
                    case 'ptr':
                    case 'exists':
                        $issues[] = new ValidationIssue('spf', "SPF uses deprecated {$mechanism} mechanism", Severity::Warning);
                        break;

                    case 'redirect':
                        if (!$value) {
                            $issues[] = new ValidationIssue('spf', "SPF redirect mechanism missing target", Severity::Error);
                        }
                        break;

                    case 'include':
                        if (!$value) {
                            $issues[] = new ValidationIssue('spf', "SPF include mechanism missing domain", Severity::Error);
                            break;
                        }

                        // Attempt DNS TXT resolution for included domain
                        try {
                            $includedTxt = $this->dns->getRecords($value, DNS_TXT);
                            $foundSpf = false;
                            foreach ($includedTxt as $rec) {
                                if (str_starts_with(strtolower($rec['txt'] ?? ''), 'v=spf1')) {
                                    $foundSpf = true;
                                    break;
                                }
                            }
                            if (!$foundSpf) {
                                $issues[] = new ValidationIssue('spf', "SPF include domain {$value} has no SPF record", Severity::Warning);
                            }
                        } catch (\Exception $e) {
                            $issues[] = new ValidationIssue('spf', "SPF include {$value} DNS lookup failed", Severity::Warning);
                        }
                        break;
                }
            }
        }

        return $issues;
    }

    /**
     * Extracts the SPF records from the DNS records
     *
     * @param array $txtRecords
     * @return array
     */
    private function extractSpf(array $txtRecords): array
    {
        return array_values(array_filter(
            array_column($txtRecords, 'txt'),
            fn(string $txt) => str_starts_with(strtolower($txt), 'v=spf1')
        ));
    }

    /**
     * Extracts the DMARC records from the DNS records
     *
     * @param array $txtRecords
     * @return array
     */
    private function extractDmarc(array $txtRecords): array
    {
        return array_values(array_filter(
            array_column($txtRecords, 'txt'),
            fn(string $txt) => str_starts_with(strtolower($txt), 'v=dmarc1')
        ));
    }

    /**
     * Extracts the DKIM records from the DNS records
     *
     * @param array $txtRecords
     * @return array
     */
    private function extractDkim(array $txtRecords): array
    {
        return array_values(array_filter(
            array_column($txtRecords, 'txt'),
            fn(string $txt) => str_starts_with(strtolower($txt), 'v=dkim1')
        ));
    }

    /**
     * Normalises a domain name
     *
     * @param string $domain
     * @return string
     */
    private function normaliseDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        if (str_starts_with($domain, 'www.')) {
            $domain = substr($domain, 4);
        }
        return $domain;
    }

    /**
     * Checks if the IP address is authorized in the SPF record
     *
     * @param string $ip
     * @param string $spf
     * @param array $visitedIncludes
     * @param array $issues
     * @return bool
     */
    private function isIpAuthorized(string $ip, string $spf, array $visitedIncludes = [], array &$issues = []): bool
    {
        $spf = strtolower($spf);
        if (preg_match_all('/ip4:([0-9\.\/]+)/', $spf, $matches)) {
            foreach ($matches[1] as $cidr) {
                try {
                    if ($this->cidrMatch($ip, $cidr)) return true;
                } catch (InvalidArgumentException $e) {
                    $issues[] = new ValidationIssue(
                        'spf',
                        sprintf('Invalid CIDR record: %s', $cidr),
                        Severity::Error
                    );
                }
            }
        }
        if (preg_match_all('/ip6:([a-f0-9:\/]+)/', $spf, $matches)) {
            foreach ($matches[1] as $cidr) {
                try {
                    if ($this->cidrMatch($ip, $cidr)) return true;
                } catch (InvalidArgumentException $e) {
                    $issues[] = new ValidationIssue(
                        'spf',
                        sprintf('Invalid CIDR record: %s', $cidr),
                        Severity::Error
                    );
                }
            }
        }

        if (preg_match_all('/include:([a-z0-9\.\-]+)/', $spf, $matches)) {
            foreach ($matches[1] as $includeDomain) {
                if (in_array($includeDomain, $visitedIncludes, true)) {
                    continue;
                }
                $records = $this->dns->getRecords($includeDomain, DNS_TXT);
                foreach ($records as $rec) {
                    $txt = $rec['txt'] ?? '';
                    if (str_starts_with(strtolower($txt), 'v=spf1')) {
                        if ($this->isIpAuthorized($ip, $txt, array_merge($visitedIncludes, [$includeDomain]), $issues)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the IP address matches the CIDR notation
     *
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function cidrMatch(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);

        // Append default CIDR if missing
        if (strpos($cidr, '/') === false) {
            $cidr .= strpos($ip, ':') === false ? '/32' : '/128';
        }

        list($subnet, $mask) = explode('/', $cidr);
        $mask = (int)$mask;

        // Detect IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            if ($mask < 0 || $mask > 32) {
                throw new InvalidArgumentException(sprintf('Invalid IPv4 CIDR mask %d for IP %s', $mask, $ip));
            }

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = $mask === 0 ? 0 : (0xFFFFFFFF << (32 - $mask)) & 0xFFFFFFFF;

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        // Detect IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            if ($mask < 0 || $mask > 128) {
                throw new InvalidArgumentException(sprintf('Invalid IPv6 CIDR mask %d for IP %s', $mask, $ip));
            }

            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            $maskLeft = $mask;

            for ($i = 0; $i < 16; $i++) {
                $bits = min($maskLeft, 8);
                $maskLeft -= $bits;
                $maskByte = (0xFF << (8 - $bits)) & 0xFF;
                if (($ipBin[$i] & $maskByte) !== ($subnetBin[$i] & $maskByte)) {
                    return false;
                }
            }
            return true;
        }

        throw new InvalidArgumentException(sprintf('Invalid IP address: %s', $ip));
    }

    /**
     * Validate TLS certificate of an MX host
     *
     * @param string $host
     * @return bool
     */
    private function validateMxTls(string $host): bool
    {
        $port = 25;
        $timeout = 5;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        $client = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$client) {
            return false;
        }

        $params = stream_context_get_params($client);
        $cert = $params['options']['ssl']['peer_certificate'] ?? null;
        if (!$cert) return false;

        $valid = openssl_x509_checkpurpose($cert, X509_PURPOSE_SSL_CLIENT, [$host]);
        fclose($client);

        return $valid;
    }
}