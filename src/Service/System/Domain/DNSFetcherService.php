<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\System\Domain;

class DNSFetcherService
{
	private array $dmarcRecords = [];
	private array $mxRecords = [];
	private array $spfRecords = [];
	private array $txtRecords = [];

	/**
	 * Fetches all DMARC records for a domain.
	 *
	 * @param string $domain
	 * @return array
	 */
	public function fetchDMARCRecords(string $domain): array
	{
		$records = dns_get_record('_dmarc.' . $domain, DNS_TXT);

		foreach ($records as $record) {
			if (isset($record['txt']) && stripos($record['txt'], 'v=DMARC1') === 0) {
				$this->dmarcRecords[] = $record['txt'];
			}
		}

		return $this->dmarcRecords;
	}

	/**
	 * Fetches all TXT records for a domain. SPF records are also stored separately.
	 * This will include for example DLKIM with name <prefix>._domainkey.<domain>
	 * with a calue of v=DKIM1; k=rsa; p=<key>
	 *
	 * @param string $domain
	 * @return array
	 */
	public function fetchTXTRecords(string $domain): array
	{
		$records = dns_get_record($domain, DNS_TXT);

		foreach ($records as $record) {
			if (isset($record['txt'])) {
				if (stripos($record['txt'], 'v=spf1') === 0) {
	 				$this->spfRecords[] = $record['txt'];
				}
				$this->txtRecords[] = $record['txt'];
			}
		}

		return $this->txtRecords;
	}

	/**
	 * Fetches just SPF records for a domain.
	 *
	 * @param string $domain
	 * @return array
	 */
	public function fetchSPFRecords(string $domain): array
	{
		$records = dns_get_record($domain, DNS_TXT);

		foreach ($records as $record) {
			if (isset($record['txt']) && stripos($record['txt'], 'v=spf1') === 0) {
	 			$this->spfRecords[] = $record['txt'];
			}
		}

		return $this->spfRecords;
	}

	/**
	 * Fetches MX records for a domain.
	 *
	 * @param string $domain
	 * @return array
	 */
	public function fetchMXRecords(string $domain): array
	{
		$this->mxRecords = dns_get_record($domain, DNS_MX);
		usort($this->mxRecords, function ($a, $b) {
			return $a['pri'] <=> $b['pri'];
		});
		return $this->mxRecords;
	}

	/**
	 * Parses a DMARC record.
	 *
	 * @param string $dmarcRecord
	 * @return array
	 */
	public function parseDMARCRecord(string $dmarcRecord): array
	{
		$parsed = [];
		$parts = explode(';', $dmarcRecord);

		foreach ($parts as $part) {
			$part = trim($part);
			if (strpos($part, '=') !== false) {
				[$key, $value] = explode('=', $part, 2);
				$parsed[trim($key)] = trim($value);
			}
		}

		return $parsed;
	}

	/**
	 * Validates SPF records.
	 *
	 * @return array
	 */
	public function validateSPFRecord(): array
	{
		if (empty($this->spfRecords)) {
			return ['No SPF record found'];
		}

		$errors = [];
		if (count($this->spfRecords) > 1) {
			$errors[] = 'Multiple SPF records found';
		}
		if (strpos($this->spfRecords[0], '+all') !== false) {
			$errors[] = 'SPF record allows anyone to send email';
		}
		if (stripos($this->spfRecords[0], 'v=spf1') === 0) {
			$errors[] = 'SPF record does not start with v=spf1';
		}
		return $errors;
	}

	/**
	 * Validates DMARC records.
	 *
	 * @return array
	 */
	public function validateDmarc(): array
	{
		$errors = [];
		if (empty($this->dmarcRecords)) {
			$errors[] = 'No DMARC record found';
			return $errors;
		}
		if (count($this->dmarcRecords) > 1) {
			$errors[] = 'Multiple DMARC records found';
		}
		foreach ($this->dmarcRecords as $record) {
			$parsed = $this->parseDMARCRecord($record);
			if (!isset($parsed['p'])) {
				$errors[] = 'DMARC record missing policy (p) tag';
			}
			if (($parsed['v'] ?? null) !== 'DMARC1') {
        		$errors[] = 'Missing or invalid DMARC version';
    		}
			if (!isset($parsed['p']) ||
				!in_array($parsed['p'], ['none', 'quarantine', 'reject'], true)) {
				$errors[] = 'Missing or invalid policy (p)';
			}

			if (isset($parsed['rua']) &&
				!preg_match('/^mailto:.+@.+$/', $parsed['rua'])) {
				$errors[] = 'Invalid rua address';
			}

			if (isset($parsed['ruf']) &&
				!preg_match('/^mailto:.+@.+$/', $parsed['ruf'])) {
				$errors[] = 'Invalid ruf address';
			}
		}
		return $errors;
	}
}
