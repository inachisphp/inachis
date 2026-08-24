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
use Symfony\Component\HttpFoundation\Response;
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
            return new JsonResponse(null, Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $content = $request->getContent();
        if (!$content) {
            return new JsonResponse(status: 204);
        }

        try {
            /**
             * @var array<'csp-report'|int<0, max>, array{
             *     age: int,
             *     type: string,
             *     url: string,
             *     user_agent: string,
             *     body: array{
             *         blockedUrl: string,
             *         disposition: string,
             *         effectiveDirective: string,
             *         originalPoliy: string,
             *         statusCode: int
             *     }
             * }|array{
             *     document-uri: string,
             *     referrer?: string,
             *     violated-directive: string,
             *     effective-directive: string,
             *     original-policy: string,
             *     blocked-uri: string,
             *     status-code: int
             * }|null>|null $payload
             */
            $payload = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            if (is_array($payload)) {
                $processor->process(
                    $payload,
                    $request->headers->get('User-Agent'),
                );
            }
        } catch (\Throwable $e) {
        }

        return new JsonResponse(status: 204);
    }
}
