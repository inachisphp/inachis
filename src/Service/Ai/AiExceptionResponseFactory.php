<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai;

use Inachis\Exception\Ai\AiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

readonly class AiExceptionResponseFactory
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function create(
        AiException $exception,
        string $operation,
    ): JsonResponse {
        $context = [
            'operation' => $operation,
            'exception' => $exception::class,
            'error_code' => $exception->getErrorCode(),
            'http_status' => $exception->getHttpStatus(),
            'message' => $exception->getMessage(),
            'provider' => $exception->provider,
        ];

        if ($exception->getPrevious()) {
            $context['previous_exception'] = $exception->getPrevious()::class;
            $context['previous_message'] = $exception->getPrevious()->getMessage();
        }

        if (null !== $exception->providerStatusCode) {
            $context['provider_status_code'] = $exception->providerStatusCode;
        }

        $this->logger->error('AI request failed.', $context);

        return new JsonResponse([
            'error' => $exception->getUserMessage(),
            'code' => $exception->getErrorCode(),
        ], $exception->getHttpStatus());
    }
}
