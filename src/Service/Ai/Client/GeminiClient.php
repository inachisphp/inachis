<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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

	private const string DEFAULT_MODEL = 'gemini-flash-latest';

	public function __construct(
		private HttpClientInterface $httpClient,
		#[Autowire('%env(GEMINI_API_KEY)%')]
		private ?string $apiKey = null,
	) {}

	public function isConfigured(): bool
	{
		return !empty($this->apiKey);
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

		$endpoint = sprintf(
			'%s/%s:generateContent',
			self::BASE_URL,
			$model,
		);

		try {
			$response = $this->httpClient->request('POST', $endpoint, [
				'headers' => [
					'Content-Type' => 'application/json',
					'x-goog-api-key' => $this->apiKey,
				],
				'json' => $payload,
				'timeout' => 30,
			]);

			$statusCode = $response->getStatusCode();

			// Prevent Symfony's HTTP client from throwing before we can
			// classify the provider response ourselves.
			$content = $response->getContent(false);
		} catch (TransportExceptionInterface $e) {
			throw new AiTemporaryException(
				'Unable to communicate with the Gemini API.',
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

		if (in_array($statusCode, [401, 403], true)) {
			return new AiConfigurationException(
				$message ?? 'Gemini rejected the configured API credentials.',
				provider: 'gemini',
			);
		}

		if (429 === $statusCode) {
			return new AiRateLimitException(
				$message ?? 'Gemini API rate limit exceeded.',
				provider: 'gemini',
			);
		}

		if (in_array($statusCode, [408, 425, 500, 502, 503, 504], true)) {
			return new AiTemporaryException(
				$message ?? 'Gemini API is temporarily unavailable.',
				provider: 'gemini',
			);
		}

		return new AiProviderException(
			$message ?? sprintf(
				'Gemini API returned HTTP %d.',
				$statusCode,
			),
			provider: 'gemini',
			providerStatusCode: $statusCode,
		);
	}

	private function extractErrorMessage(string $content): ?string
	{
		if ('' === trim($content)) {
			return null;
		}

		$data = json_decode($content, true);

		if (!is_array($data)) {
			return null;
		}

		$message = $data['error']['message'] ?? null;

		return is_string($message) && '' !== trim($message)
			? trim($message)
			: null;
	}
}
