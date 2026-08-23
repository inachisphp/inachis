<?php

declare(strict_types=1);

namespace Inachis\MessageHandler;

use Doctrine\DBAL\Connection;
use Inachis\Message\RestoreBackupMessage;
use Inachis\Service\System\BackupValidationService;
use Inachis\Service\System\DatabasePurgeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler]
class RestoreBackupHandler
{
    public function __construct(
        private readonly Connection $connection,
        private readonly BackupValidationService $validator,
        private readonly DatabasePurgeService $purgeService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function __invoke(RestoreBackupMessage $message): void
    {
        $jobId = $message->jobId;
        $this->updateProgress($jobId, 0, 'Validating backup file...');

        // Step 1: Validate file
        $this->validator->validate($message->filePath);

        // Step 2: Auto-truncate non-excluded database tables
        $this->updateProgress($jobId, 10, 'Truncating existing table data...');
        $this->purgeService->purgeUserTables();

        // Step 3: Count approximate lines for progress tracking
        $totalLines = $this->countLines($message->filePath);
        $processedLines = 0;

        $gz = gzopen($message->filePath, 'rb');
        if ($gz === false) {
            throw new \RuntimeException(sprintf('Unable to open backup file at "%s"', $message->filePath));
        }

        /** @var \PDO $pdo Bypass DBAL query logging middleware */
        $pdo = $this->connection->getNativeConnection();
        if (!$pdo instanceof \PDO) {
            throw new \RuntimeException('Native PDO connection is required for high-performance restore.');
        }

        $pdo->beginTransaction();

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            $statementBuffer = '';
            $currentTable = 'Initializing...';
            $isExcludedTable = false;

            while (!gzeof($gz)) {
                $line = gzgets($gz, 8192);
                if ($line === false) {
                    continue;
                }

                $processedLines++;
                $trimmed = trim($line);

                // Update current table context and check exclusion rule
                if (str_starts_with($trimmed, '-- Data for table')) {
                    $currentTable = trim(str_replace(['--', 'Data for table', '`'], '', $trimmed));
                    $isExcludedTable = in_array($currentTable, DatabasePurgeService::EXCLUDED_TABLES, true);
                    continue;
                }

                // Skip comments, empty lines, or lines belonging to an excluded table
                if ($trimmed === '' || str_starts_with($trimmed, '--') || $isExcludedTable) {
                    continue;
                }

                $statementBuffer .= $line;

                if (str_ends_with(rtrim($statementBuffer, "\r\n\t "), ';')) {
                    $pdo->exec($statementBuffer);
                    $statementBuffer = '';

                    // Update progress periodically
                    if ($processedLines % 50 === 0 && $totalLines > 0) {
                        $percent = (int) min(95, floor(($processedLines / $totalLines) * 100));
                        $this->updateProgress(
                            $jobId,
                            $percent,
                            sprintf('Importing table: %s', $currentTable)
                        );
                    }
                }
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
            $pdo->commit();

            $this->updateProgress($jobId, 100, 'Restore completed successfully.');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
            } catch (\Throwable) {
                // Ignore secondary cleanup errors on failure
            }

            $this->updateProgress($jobId, -1, 'Restore failed: ' . $e->getMessage());
            throw $e;
        } finally {
            gzclose($gz);
        }
    }

    private function updateProgress(string $jobId, int $percent, string $status): void
    {
        $item = $this->cache->getItem('restore_progress_' . $jobId);
        $item->set([
            'percent' => $percent,
            'status' => $status,
            'updatedAt' => (new \DateTimeImmutable())->format('H:i:s'),
        ]);
        $this->cache->save($item);
    }

    private function countLines(string $filePath): int
    {
        $gz = gzopen($filePath, 'rb');
        if ($gz === false) {
            return 0;
        }

        $lines = 0;
        while (!gzeof($gz)) {
            $line = gzgets($gz, 8192);
            if ($line === false) {
                break;
            }
            $lines++;
        }
        gzclose($gz);

        return $lines;
    }
}
