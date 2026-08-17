<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Message to trigger a user data database backup.
 */
#[AsMessage]
class CreateBackupMessage
{
    public function __construct(
        public string $outputDir = 'var/backups',
        public ?string $requestedBy = null,
    ) {
    }
}
