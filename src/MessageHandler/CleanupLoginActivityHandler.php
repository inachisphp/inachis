<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\MessageHandler;

use Inachis\Message\CleanupLoginActivityMessage;
use Inachis\Repository\User\LoginActivityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for CleanupLoginActivityMessage.
 */
#[AsMessageHandler]
class CleanupLoginActivityHandler
{
    public function __construct(
        private readonly LoginActivityRepository $repo,
    ) {
    }

    /**
     * @return void
     */
    public function __invoke(CleanupLoginActivityMessage $message)
    {
        $now = new \DateTimeImmutable();
        $successRetention = $now->modify('-12 months');
        $failureRetention = $now->modify('-90 days');

        $callback = $message->dryRun
            ? fn (int $batchDeleted, int $totalDeleted) => null
            : fn (int $batchDeleted, int $totalDeleted) => print "Deleted batch $batchDeleted (total $totalDeleted)\n";

        $successDeleted = $this->repo->deleteOlderThan('success', $successRetention, $message->batchSize, $callback);
        $failureDeleted = $this->repo->deleteOlderThan('failure', $failureRetention, $message->batchSize, $callback);

        if ($message->dryRun) {
            $successCount = $this->repo->countOlderThan('success', $successRetention);
            $failureCount = $this->repo->countOlderThan('failure', $failureRetention);

            echo "Dry run: $successCount successful, $failureCount failed would be deleted\n";
        } else {
            echo "Cleanup finished: $successDeleted successful, $failureDeleted failed records deleted\n";
        }
    }
}
