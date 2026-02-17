<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Performance;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Checks the HTTP latency
 */
final class HttpLatencyCheck implements CheckInterface
{
	/**
	 * The internal URL to measure
	 */
    private string $internalUrl;

	/**
	 * The public URL to measure
	 */
    private string $publicUrl;

	/**
	 * Constructor
	 *
	 * @param HttpClientInterface $client The HTTP client
	 * @param KernelInterface $kernel The kernel
	 * @param int $samples The number of samples to take
	 * @param float $timeout The timeout in seconds
	 */
    public function __construct(
        private readonly HttpClientInterface $client,
        KernelInterface $kernel,
        private readonly int $samples = 3,
        private readonly float $timeout = 5.0,
    ) {
        // internal loopback (no proxy)
        $this->internalUrl = 'https://127.0.0.1/health';

        // public entry point (may go through proxy)
        $this->publicUrl = $_SERVER['APP_URL']
            ?? 'https://localhost/health';
    }

	/**
	 * Get the ID of the check
	 *
	 * @return string
	 */
    public function getId(): string { return 'http_latency'; }

	/**
	 * Get the label of the check
	 *
	 * @return string
	 */
    public function getLabel(): string { return 'HTTP Latency'; }

	/**
	 * Get the section of the check
	 *
	 * @return string
	 */
    public function getSection(): string { return 'Environment'; }

	/**
	 * Run the check
	 *
	 * @return CheckResult
	 */
    public function run(): CheckResult
    {
        try {
            $internal = $this->measure($this->internalUrl);
            $public   = $this->measure($this->publicUrl);

            $issues = [];
            $severity = 'ok';

            // Cold start detection
            $coldPenalty = $internal['samples'][0]['total'] - $internal['average'];
            if ($coldPenalty > 200) {
                $issues[] = 'Cold start latency detected';
                $severity = 'warning';
            }

            // Reverse proxy overhead
            $proxyOverhead = $public['average'] - $internal['average'];
            if ($proxyOverhead > 150) {
                $issues[] = 'High reverse proxy overhead';
                $severity = 'warning';
            }

            // Absolute thresholds
            if ($public['average'] > 1000) {
                $severity = 'error';
                $issues[] = 'High HTTP latency';
            } elseif ($public['average'] > 400) {
                $severity = $severity === 'error' ? 'error' : 'warning';
            }

            // Docker / container detection
            if ($this->isContainer()) {
                $issues[] = 'Running inside container';
            }

            $summary = sprintf(
                'Internal avg: %.1fms | Public avg: %.1fms | DNS: %.1fms | Connect: %.1fms | TLS: %.1fms | TTFB: %.1fms',
                $internal['average'],
                $public['average'],
                $internal['dns'],
                $internal['connect'],
                $internal['tls'],
                $internal['ttfb']
            );

            if ($issues) {
                $summary .= ' | ' . implode('; ', $issues);
            }

            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                $severity,
                $public['average'] . ' ms',
                $summary,
                $severity === 'ok' ? null : 'Investigate network, proxy, or container performance.',
                $this->getSection(),
                'high'
            );

        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                'HTTP test failed',
                $e->getMessage(),
                'Verify application availability.',
                $this->getSection(),
                'high'
            );
        }
    }

	/**
	 * Measure the HTTP latency
	 *
	 * @param string $url The URL to measure
	 * @return array The measurement results
	 */
    private function measure(string $url): array
    {
        $samples = [];
        $dnsTimes = [];
        $connectTimes = [];
        $tlsTimes = [];
        $ttfbTimes = [];

        for ($i = 0; $i < $this->samples; $i++) {
            $start = hrtime(true);
            $response = $this->client->request('HEAD', $url, [
                'timeout' => $this->timeout,
            ]);
            $response->getStatusCode();
            $duration = (hrtime(true) - $start) / 1e6; // ms

            $info = $response->getInfo();
            $samples[] = ['total' => $duration];
            $dnsTimes[] = ($info['namelookup_time'] ?? 0) * 1000;
            $connectTimes[] = ($info['connect_time'] ?? 0) * 1000;
            $tlsTimes[] = ($info['ssl_time'] ?? 0) * 1000;
            $ttfbTimes[] = ($info['starttransfer_time'] ?? 0) * 1000;
        }

        return [
            'samples' => $samples,
            'average' => round(array_sum(array_column($samples, 'total')) / count($samples), 1),
            'dns' => round(array_sum($dnsTimes)/count($dnsTimes),1),
            'connect' => round(array_sum($connectTimes)/count($connectTimes),1),
            'tls' => round(array_sum($tlsTimes)/count($tlsTimes),1),
            'ttfb' => round(array_sum($ttfbTimes)/count($ttfbTimes),1),
        ];
    }

	/**
	 *
	 */
    private function isContainer(): bool
    {
        return file_exists('/.dockerenv') ||
            (file_exists('/proc/1/cgroup') && str_contains(file_get_contents('/proc/1/cgroup'), 'docker'));
    }
}