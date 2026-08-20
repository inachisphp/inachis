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

readonly class OpenAiClient
{
    private const string BASE_URL = 'https://api.openai.com/v1';

    private const string DEFAULT_MODEL = 'gpt-4o-mini';

    private const string DEFAULT_TTS_MODEL = 'tts-1';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private ?string $apiKey = null,
    ) {}

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Sends a request to the OpenAI Chat Completions API.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function createChatCompletion(
        array $payload,
        string $model = self::DEFAULT_MODEL,
    ): array {
        if (!$this->isConfigured()) {
            throw new AiConfigurationException(
                'OpenAI API key is not configured.',
            );
        }

        $payload['model'] ??= $model;

        try {
            $response = $this->httpClient->request(
                'POST',
                self::BASE_URL . '/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => 30,
                ],
            );

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new AiTemporaryException(
                'Unable to communicate with the OpenAI API.',
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new AiProviderException(
                'An unexpected error occurred while communicating with the OpenAI API.',
                provider: 'openai',
                previous: $e,
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw $this->createException($statusCode, $content);
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            throw new AiResponseException(
                'OpenAI returned an invalid JSON response.',
            );
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Sends a request to the OpenAI Audio Speech API and returns raw binary MP3 content.
     *
     * @param array<string, mixed> $payload
     */
    public function createSpeech(
        array $payload,
        string $model = self::DEFAULT_TTS_MODEL,
    ): string {
        if (!$this->isConfigured()) {
            throw new AiConfigurationException(
                'OpenAI API key is not configured.',
            );
        }

        $payload['model'] ??= $model;

        try {
            $response = $this->httpClient->request(
                'POST',
                self::BASE_URL . '/audio/speech',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => 30,
                ],
            );

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new AiTemporaryException(
                'Unable to communicate with the OpenAI API.',
                previous: $e,
            );
        } catch (\Throwable $e) {
            throw new AiProviderException(
                'An unexpected error occurred while communicating with the OpenAI API.',
                provider: 'openai',
                previous: $e,
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw $this->createException($statusCode, $content);
        }

        return $content; // Raw binary MP3
    }

    private function createException(
        int $statusCode,
        string $content,
    ): AiConfigurationException|AiRateLimitException|AiTemporaryException|AiProviderException {
        $message = $this->extractErrorMessage($content);

        if (in_array($statusCode, [401, 403], true)) {
            return new AiConfigurationException(
                $message ?? 'OpenAI rejected the configured API credentials.',
            );
        }

        if (429 === $statusCode) {
            return new AiRateLimitException(
                $message ?? 'OpenAI API rate limit exceeded.',
            );
        }

        if (in_array($statusCode, [408, 409, 425, 500, 502, 503, 504], true)) {
            return new AiTemporaryException(
                $message ?? 'OpenAI API is temporarily unavailable.',
            );
        }

        return new AiProviderException(
            $message ?? sprintf(
                'OpenAI API returned HTTP %d.',
                $statusCode,
            ),
            provider: 'openai',
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
