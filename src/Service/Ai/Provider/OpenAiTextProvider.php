<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\OpenAiClient;

readonly class OpenAiTextProvider implements AiTextProviderInterface
{
    public function __construct(
        private OpenAiClient $client,
    ) {}

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function generateText(
        string $prompt,
        ?string $systemPrompt = null,
        bool $jsonMode = false,
    ): string {
        $messages = [];

        if (null !== $systemPrompt && '' !== trim($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $payload = [
            'messages' => $messages,
            'max_tokens' => 1000,
        ];

        if ($jsonMode) {
            $payload['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $data = $this->client->createChatCompletion($payload);

        return $this->extractGeneratedText($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractGeneratedText(array $data): string
    {
        $choices = $data['choices'] ?? null;

        if (!is_array($choices) || [] === $choices) {
            throw new AiResponseException(
                'OpenAI response did not contain any choices.',
                provider: 'openai',
            );
        }

        $choice = $choices[0] ?? null;

        if (!is_array($choice)) {
            throw new AiResponseException(
                'OpenAI response contained an invalid choice.',
                provider: 'openai',
            );
        }

        $message = $choice['message'] ?? null;

        if (!is_array($message)) {
            throw new AiResponseException(
                'OpenAI response did not contain a message.',
                provider: 'openai',
            );
        }

        $content = $message['content'] ?? null;

        if (!is_string($content)) {
            throw new AiResponseException(
                'OpenAI response did not contain generated text.',
                provider: 'openai',
            );
        }

        return $content;
    }
}
