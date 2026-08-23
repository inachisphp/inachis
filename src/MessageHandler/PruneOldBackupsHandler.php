<?php

declare(strict_types=1);

namespace Inachis\MessageHandler;

use Inachis\Message\PruneOldBackupsMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PruneOldBackupsHandler
{
    public function __invoke(PruneOldBackupsMessage $message): int
    {
        if (!is_dir($message->backupDir)) {
            return 0;
        }

        $files = glob(rtrim($message->backupDir, '/') . '/*.sql.gz');
        if ($files === false) {
            return 0;
        }

        $threshold = (new \DateTimeImmutable())->modify(sprintf('-%d days', $message->retentionDays))->getTimestamp();
        $deletedCount = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}
