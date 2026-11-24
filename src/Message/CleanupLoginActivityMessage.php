<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Message to cleanup login activity.
 */
#[AsMessage]
class CleanupLoginActivityMessage
{
    public function __construct(
        public bool $dryRun = false,
        public int $batchSize = 1000,
    ) {
    }
}
