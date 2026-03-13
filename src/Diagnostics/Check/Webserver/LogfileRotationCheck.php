<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics\Check\Webserver;

use Inachis\Diagnostics\CheckInterface;
use Inachis\Diagnostics\CheckResult;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Checks if the log files are healthy
 */
final class LogfileRotationCheck implements CheckInterface
{
    /**
     * The state file name
     */
    private const STATE_FILE = 'log_health_state.json';

    /**
     * The log directory
     */
    private string $logDir;

    /**
     * The cache directory
     */
    private string $cacheDir;

    /**
     * The environment
     */
    private string $environment;

    /**
     * Constructor
     *
     * @param KernelInterface $kernel The kernel to use for the check
     * @param int $warningSizeMb The warning size in MB
     * @param int $errorSizeMb The error size in MB
     */
    public function __construct(
        KernelInterface $kernel,
        private readonly int $warningSizeMb = 200,
        private readonly int $errorSizeMb = 500,
    ) {
        $this->logDir = $kernel->getLogDir();
        $this->cacheDir = $kernel->getCacheDir();
        $this->environment = $kernel->getEnvironment();
    }

    /**
     * Get the ID of the check
     *
     * @return string
     */
    public function getId(): string { return 'log_health'; }

    /**
     * Get the label of the check
     *
     * @return string
     */
    public function getLabel(): string { return 'Log Health'; }

    /**
     * Get the section of the check
     *
     * @return string
     */
    public function getSection(): string { return 'Webserver'; }

    /**
     * Run the check
     *
     * @return CheckResult
     */
    public function run(): CheckResult
    {
        if (!is_dir($this->logDir)) {
            return $this->result('warning', 'Missing log directory', $this->logDir);
        }

        $now = time();
        $files = glob($this->logDir . '/*.log') ?: [];
        $statePath = $this->cacheDir . '/' . self::STATE_FILE;

        $previous = file_exists($statePath)
            ? json_decode(file_get_contents($statePath), true)
            : [];

        $current = [];
        $issues = [];
        $severity = 'ok';

        $totalSize = 0;

        foreach ($files as $file) {
            $size = filesize($file) ?: 0;
            $totalSize += $size;

            $current[$file] = [
                'size' => $size,
                'time' => $now,
            ];

            if ($size > $this->errorSizeMb * 1024 * 1024) {
                $severity = 'error';
                $issues[] = basename($file) . ' exceeds ' . $this->errorSizeMb . 'MB';
            } elseif ($size > $this->warningSizeMb * 1024 * 1024) {
                $severity = $severity === 'error' ? 'error' : 'warning';
                $issues[] = basename($file) . ' exceeds ' . $this->warningSizeMb . 'MB';
            }

            if (isset($previous[$file])) {
                $deltaBytes = $size - $previous[$file]['size'];
                $deltaTime = $now - $previous[$file]['time'];

                if ($deltaBytes > 0 && $deltaTime > 0) {
                    $bytesPerSecond = $deltaBytes / $deltaTime;

                    if ($bytesPerSecond > 1024 * 1024 / 60) {
                        $severity = 'warning';
                        $issues[] = basename($file) . ' growing rapidly';
                    }
                }
            }
        }

        $freeDisk = disk_free_space($this->logDir) ?: 0;
        $predictedSeconds = null;

        if (!empty($previous)) {
            $previousTotal = array_sum(array_column($previous, 'size'));
            $deltaBytes = $totalSize - $previousTotal;
            $deltaTime = $now - ($previous[array_key_first($previous)]['time'] ?? $now);

            if ($deltaBytes > 0 && $deltaTime > 0) {
                $growthRate = $deltaBytes / $deltaTime; // bytes/sec

                if ($growthRate > 0) {
                    $predictedSeconds = (int) ($freeDisk / $growthRate);

                    if ($predictedSeconds < 3600) {
                        $severity = 'error';
                        $issues[] = 'Disk exhaustion predicted in < 1 hour';
                    } elseif ($predictedSeconds < 86400) {
                        $severity = $severity === 'error' ? 'error' : 'warning';
                        $issues[] = 'Disk exhaustion predicted in < 24 hours';
                    }
                }
            }
        }

        if ($this->environment === 'dev') {
            if ($severity === 'warning') {
                $severity = 'ok';
            }
        }

        file_put_contents($statePath, json_encode($current));

        $summary = $issues === []
            ? 'Logs healthy'
            : implode('; ', $issues);

        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $severity,
            round($totalSize / 1024 / 1024, 1) . 'MB total',
            $summary,
            $severity === 'ok' ? null : 'Investigate log verbosity or rotation policy.',
            $this->getSection(),
            'high'
        );
    }

    /**
     * Create a check result
     *
     * @param string $status
     * @param string $value
     * @param string $details
     * @return CheckResult
     */
    private function result(string $status, string $value, string $details): CheckResult
    {
        return new CheckResult(
            $this->getId(),
            $this->getLabel(),
            $status,
            $value,
            $details,
            null,
            $this->getSection(),
            'medium'
        );
    }
}