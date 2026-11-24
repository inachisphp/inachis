<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Exception\Ai;

use Symfony\Component\HttpFoundation\Response;

class AiProviderException extends AiException
{
    public function getErrorCode(): string
    {
        return 'ai_provider';
    }

    public function getHttpStatus(): int
    {
        return Response::HTTP_BAD_GATEWAY;
    }

    public function getUserMessage(): string
    {
        return 'The AI service could not complete the request. Please try again.';
    }
}
