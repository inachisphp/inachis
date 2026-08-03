<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

/**
 * Message to cleanup login activity
 */
#[AsMessage]
class CleanupLoginActivityMessage
{
    /**
     * @param bool $dryRun
     * @param int $batchSize
     */
    public function __construct(
        public bool $dryRun = false,
        public int $batchSize = 1000
    ) {}
}