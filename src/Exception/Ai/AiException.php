<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Exception\Ai;

abstract class AiException extends \RuntimeException
{
	public function __construct(
		string $message,
		public readonly string $provider = 'unknown',
		public readonly ?int $providerStatusCode = null,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, $providerStatusCode ?? 0, $previous);
	}
	
	abstract public function getErrorCode(): string;

	abstract public function getHttpStatus(): int;

	abstract public function getUserMessage(): string;
}
