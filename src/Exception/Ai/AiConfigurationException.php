<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Ai;

use Symfony\Component\HttpFoundation\Response;

class AiConfigurationException extends AiException
{
	public function getErrorCode(): string
	{
		return 'ai_configuration';
	}

	public function getHttpStatus(): int
	{
		return Response::HTTP_BAD_REQUEST;
	}

	public function getUserMessage(): string
	{
		return 'AI generation is not currently configured.';
	}
}
