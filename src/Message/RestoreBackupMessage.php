<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Message to trigger a database restore from a backup file.
 */
#[AsMessage]
class RestoreBackupMessage
{
    public function __construct(
        public string $filePath,
        public string $jobId,
        public ?string $requestedBy = null,
    ) {
    }
}
