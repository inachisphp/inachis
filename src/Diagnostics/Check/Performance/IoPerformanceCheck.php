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

/**
 * Checks the disk I/O performance
 */
final class IoPerformanceCheck implements CheckInterface
{
	/**
	 * The cache directory
	 */
    private string $cacheDir;

	/**
	 * The test size in MB
	 */
    private int $testSizeMb = 5;

	/**
	 * Constructor
	 *
	 * @param KernelInterface $kernel The kernel
	 */
    public function __construct(KernelInterface $kernel) {
        $this->cacheDir = $kernel->getCacheDir();
    }

	/**
	 * Get the ID of the check
	 *
	 * @return string
	 */
    public function getId(): string { return 'io_performance'; }

	/**
	 * Get the label of the check
	 *
	 * @return string
	 */
    public function getLabel(): string { return 'Disk I/O Performance'; }

	/**
	 * Get the section of the check
	 *
	 * @return string
	 */
    public function getSection(): string { return 'Performance'; }

	/**
	 * Run the check
	 *
	 * @return CheckResult
	 */
    public function run(): CheckResult
    {
        $file = $this->cacheDir . '/io_test.tmp';
        $bytes = $this->testSizeMb * 1024 * 1024;

        try {
            // Generate test payload
            $data = random_bytes(1024 * 1024); // 1MB chunk

            // --------------------
            // WRITE TEST
            // --------------------
            $start = hrtime(true);

            $handle = fopen($file, 'wb');
            for ($i = 0; $i < $this->testSizeMb; $i++) {
                fwrite($handle, $data);
            }
            fflush($handle);
            fclose($handle);

            $writeDuration = (hrtime(true) - $start) / 1e9;
            $writeSpeed = round(($bytes / 1024 / 1024) / $writeDuration, 1);

            // --------------------
            // READ TEST
            // --------------------
            $start = hrtime(true);

            $handle = fopen($file, 'rb');
            while (!feof($handle)) {
                fread($handle, 8192);
            }
            fclose($handle);

            $readDuration = (hrtime(true) - $start) / 1e9;
            $readSpeed = round(($bytes / 1024 / 1024) / $readDuration, 1);

            unlink($file);

            // --------------------
            // Thresholds
            // --------------------
            $severity = 'ok';

            if ($writeSpeed < 10 || $readSpeed < 10) {
                $severity = 'error';      // extremely slow
            } elseif ($writeSpeed < 30 || $readSpeed < 30) {
                $severity = 'warning';    // slow disk
            }

            $summary = sprintf(
                'Write: %s MB/s | Read: %s MB/s',
                $writeSpeed,
                $readSpeed
            );

            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                $severity,
                $writeSpeed . ' MB/s',
                $summary,
                $severity === 'ok' ? null : 'Disk I/O performance is degraded.',
                $this->getSection(),
                'high'
            );

        } catch (\Throwable $e) {
            return new CheckResult(
                $this->getId(),
                $this->getLabel(),
                'error',
                'I/O test failed',
                $e->getMessage(),
                'Verify filesystem permissions and disk health.',
                $this->getSection(),
                'high'
            );
        }
    }
}