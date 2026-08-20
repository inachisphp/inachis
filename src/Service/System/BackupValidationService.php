<?php

declare(strict_types=1);

namespace Inachis\Service\System;

class BackupValidationService
{
    /**
     * Validates file integrity and expected structure.
     */
    public function validate(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException('Backup file does not exist or is unreadable.');
        }

        // 1. Verify Gzip Magic Number (0x1f, 0x8b)
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open file for validation.');
        }

        $bytes = fread($handle, 2);
        fclose($handle);

        if ($bytes !== "\x1f\x8b") {
            throw new \InvalidArgumentException('File is not a valid gzipped archive.');
        }

        // 2. Validate Gzip header & read top header line
        $gz = gzopen($filePath, 'rb');
        if ($gz === false) {
            throw new \InvalidArgumentException('Archive appears corrupted and cannot be opened.');
        }

        $firstLine = gzgets($gz, 1024);
        gzclose($gz);

        if ($firstLine === false || !str_contains($firstLine, 'Inachis Data-Only Database Backup')) {
            throw new \InvalidArgumentException('File header missing expected Inachis backup signature.');
        }
    }
}
