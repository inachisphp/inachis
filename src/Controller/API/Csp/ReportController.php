<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Csp;

use Inachis\Service\System\Csp\CspReportProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    #[Route('/api/csp/report', name: 'csp_report', methods: ['POST'])]
    public function __invoke(
        Request $request,
        CspReportProcessor $processor,
    ): JsonResponse {
        $contentLength = $request->headers->get('Content-Length');
        if (null === $contentLength || (int) $contentLength > 10240) {
            return new Response('', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $content = $request->getContent();
        if (!$content) {
            return new JsonResponse(status: 204);
        }

        try {
            $payload = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $processor->process(
                $payload,
                $request->headers->get('User-Agent'),
            );
        } catch (\Throwable $e) {
        }

        return new JsonResponse(status: 204);
    }
}
