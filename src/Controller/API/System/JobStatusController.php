<?php

namespace Inachis\Controller\API\System;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

class JobStatusController extends AbstractInachisController
{
    #[Route('/incp/api/status/{jobId}', name: 'incp_api_restore_status', methods: ['GET'])]
    public function getRestoreStatus(string $jobId, CacheInterface $cache): JsonResponse
    {
        $item = $cache->getItem('restore_progress_' . $jobId);

        if (!$item->isHit()) {
            return new JsonResponse(['percent' => 0, 'status' => 'Pending worker pickup…']);
        }

        return new JsonResponse($item->get());
    }
}
