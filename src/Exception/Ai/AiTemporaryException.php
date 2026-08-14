<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Ai;

use Symfony\Component\HttpFoundation\Response;

class AiTemporaryException extends AiException
{
	public function getErrorCode(): string
	{
		return 'ai_temporary';
	}

	public function getHttpStatus(): int
	{
		return Response::HTTP_SERVICE_UNAVAILABLE;
	}

	public function getUserMessage(): string
	{
		return 'The AI service is temporarily unavailable. Please try again in a moment.';
	}
}
