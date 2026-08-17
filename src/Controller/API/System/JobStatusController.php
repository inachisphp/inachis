<?php

namespace Inachis\Controller\API\System;

use Inachis\Controller\AbstractInachisController;

class JobStatusController extends AbstractInachisController
{
    #[Route('incp/api/status/{jobId}', name: 'restore_status', methods: ['GET'])]
    public function getRestoreStatus(string $jobId, CacheInterface $cache): JsonResponse
    {
        $item = $cache->getItem('restore_progress_' . $jobId);

        if (!$item->isHit()) {
            return new JsonResponse(['percent' => 0, 'status' => 'Pending worker pickup…']);
        }

        return new JsonResponse($item->get());
    }
}
