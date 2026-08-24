<?php

namespace Inachis\Controller\API\System;

use Inachis\Controller\AbstractInachisController;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class JobStatusController extends AbstractInachisController
{
    #[Route('/incp/api/status/{jobId}', name: 'incp_api_restore_status', methods: ['GET'])]
    public function getRestoreStatus(string $jobId, CacheItemPoolInterface $cache): JsonResponse
    {
        $item = $cache->getItem('restore_progress_' . $jobId);

        if (!$item->isHit()) {
            return new JsonResponse(['percent' => 0, 'status' => 'Pending worker pickup…']);
        }

        /** @var array<string, mixed> $data */
        $data = $item->get();

        return new JsonResponse($data);
    }
}
