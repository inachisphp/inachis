<?php

declare(strict_types=1);

namespace Inachis\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage]
class PruneOldBackupsMessage
{
    public function __construct(
        public int $retentionDays = 30,
        public string $backupDir = 'var/backups',
    ) {
    }
}
