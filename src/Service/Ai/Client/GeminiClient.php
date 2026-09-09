<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Ai\Client;

use Inachis\Exception\Ai\AiConfigurationException;
use Inachis\Exception\Ai\AiProviderException;
use Inachis\Exception\Ai\AiRateLimitException;
use Inachis\Exception\Ai\AiResponseException;
use Inachis\Exception\Ai\AiTemporaryException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class GeminiClient
{
	private const string BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

	/**
	 * Use a specific stable model rather than Google's moving "latest" alias.
	 */
	private const string DEFAULT_MODEL = 'gemini-3.8-flash';

	public function __construct(
		private HttpClientInterface $httpClient,
		#[Autowire('%env(GEMINI_API_KEY)%')]
		private ?string $apiKey = null,
	) {}

	public function isConfigured(): bool
	{
		return null !== $this->apiKey && '' !== trim($this->apiKey);
	}

	/**
	 * Sends a generateContent request to Gemini.
	 *
	 * @param array<string, mixed> $payload
	 *
	 * @return array<string, mixed>
	 */
	public function generateContent(
		array $payload,
		string $model = self::DEFAULT_MODEL,
	): array {
		if (!$this->isConfigured()) {
			throw new AiConfigurationException(
				'Gemini API key is not configured.',
				provider: 'gemini',
			);
		}

		$model = trim($model);

		if ('' === $model) {
			throw new AiConfigurationException(
				'Gemini model is not configured.',
				provider: 'gemini',
			);
		}

		$endpoint = sprintf(
			'%s/%s:generateContent',
			self::BASE_URL,
			rawurlencode($model),
		);

		try {
			$response = $this->httpClient->request('POST', $endpoint, [
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
					'x-goog-api-key' => $this->apiKey,
				],
				'json' => $payload,
				'timeout' => 60,
				'max_duration' => 300,
			]);

			$statusCode = $response->getStatusCode();

			// Do not allow Symfony to throw on non-2xx responses. We need
			// the provider response so we can classify the exception.
			$content = $response->getContent(false);
		} catch (TransportExceptionInterface $e) {
			throw new AiTemporaryException(
				sprintf(
					'Gemini API request failed: %s',
					$e->getMessage(),
				),
				provider: 'gemini',
				previous: $e,
			);
		} catch (\Throwable $e) {
			throw new AiProviderException(
				'An unexpected error occurred while communicating with the Gemini API.',
				provider: 'gemini',
				previous: $e,
			);
		}

		if ($statusCode < 200 || $statusCode >= 300) {
			throw $this->createException($statusCode, $content);
		}

		$data = json_decode($content, true);

		if (!is_array($data)) {
			throw new AiResponseException(
				'Gemini returned an invalid JSON response.',
				provider: 'gemini',
			);
		}

		/** @var array<string, mixed> $data */
		return $data;
	}

	private function createException(
		int $statusCode,
		string $content,
	): AiConfigurationException|AiRateLimitException|AiTemporaryException|AiProviderException {
		$message = $this->extractErrorMessage($content);

		return match (true) {
			401 === $statusCode,
			403 === $statusCode => new AiConfigurationException(
				$message ?? 'Gemini rejected the configured API credentials.',
				provider: 'gemini',
			),

			429 === $statusCode => new AiRateLimitException(
				$message ?? 'Gemini API rate limit exceeded.',
				provider: 'gemini',
			),

			in_array($statusCode, [408, 425, 500, 502, 503, 504], true) => new AiTemporaryException(
				$message ?? 'Gemini API is temporarily unavailable.',
				provider: 'gemini',
			),

			default => new AiProviderException(
				$message ?? sprintf(
					'Gemini API returned HTTP %d.',
					$statusCode,
				),
				provider: 'gemini',
				providerStatusCode: $statusCode,
			),
		};
	}

	private function extractErrorMessage(string $content): ?string
	{
		if ('' === trim($content)) {
			return null;
		}

		try {
			$data = json_decode(
				$content,
				true,
				512,
				JSON_THROW_ON_ERROR,
			);
		} catch (\JsonException) {
			return null;
		}

		if (!is_array($data)) {
			return null;
		}

		$error = $data['error'] ?? null;

		if (is_array($error)) {
			$message = $error['message'] ?? null;

			if (is_string($message) && '' !== trim($message)) {
				return $message;
			}
		}

		$message = $data['message'] ?? null;

		if (is_string($message) && '' !== trim($message)) {
			return $message;
		}

		return null;
	}
}
