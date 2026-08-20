<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Ai;

use Symfony\Component\HttpFoundation\Response;

class AiRateLimitException extends AiException
{
	public function getErrorCode(): string
	{
		return 'ai_rate_limit';
	}

	public function getHttpStatus(): int
	{
		return Response::HTTP_TOO_MANY_REQUESTS;
	}

	public function getUserMessage(): string
	{
		return 'AI generation is temporarily unavailable because the provider rate limit has been reached. Please try again later.';
	}
}
