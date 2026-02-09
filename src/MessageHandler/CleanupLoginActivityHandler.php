<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\MessageHandler;

use Inachis\Message\CleanupLoginActivityMessage;
use Inachis\Repository\LoginActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler for CleanupLoginActivityMessage
 */
#[AsMessageHandler]
class CleanupLoginActivityHandler
{
    /**
     * @param LoginActivityRepository $repo
     * @param EntityManagerInterface $em
     */
    public function __construct(
        private readonly LoginActivityRepository $repo,
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * @param CleanupLoginActivityMessage $message
     * @return void
     */
    public function __invoke(CleanupLoginActivityMessage $message)
    {
        $now = new \DateTimeImmutable();
        $successRetention = $now->modify('-12 months');
        $failureRetention = $now->modify('-90 days');

        $callback = $message->dryRun
            ? fn(int $batchDeleted, int $totalDeleted) => null
            : fn(int $batchDeleted, int $totalDeleted) => print("Deleted batch $batchDeleted (total $totalDeleted)\n");

        $successDeleted = $this->repo->deleteOlderThan('success', $successRetention, $message->batchSize, $callback);
        $failureDeleted = $this->repo->deleteOlderThan('failure', $failureRetention, $message->batchSize, $callback);

        if ($message->dryRun) {
            $successCount = $this->repo->countOlderThan('success', $successRetention);
            $failureCount = $this->repo->countOlderThan('failure', $failureRetention);

            print("Dry run: $successCount successful, $failureCount failed would be deleted\n");
        } else {
            print("Cleanup finished: $successDeleted successful, $failureDeleted failed records deleted\n");
        }
    }
}
