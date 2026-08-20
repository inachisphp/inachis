<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\GeminiClient;

readonly class GeminiTextProvider implements AiTextProviderInterface
{
    public function __construct(
        private GeminiClient $client,
    ) {}

    public function getName(): string
    {
        return 'gemini';
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
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
        ];

        if (null !== $systemPrompt && '' !== trim($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [
                    [
                        'text' => $systemPrompt,
                    ],
                ],
            ];
        }

        if ($jsonMode) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
            ];
        }

        $data = $this->client->generateContent($payload);

        return $this->extractGeneratedText($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractGeneratedText(array $data): string
    {
        $candidates = $data['candidates'] ?? null;

        if (!is_array($candidates) || [] === $candidates) {
            throw new AiResponseException(
                'Gemini response did not contain any candidates.',
                provider: 'gemini',
            );
        }

        $candidate = $candidates[0] ?? null;

        if (!is_array($candidate)) {
            throw new AiResponseException(
                'Gemini response contained an invalid candidate.',
                provider: 'gemini',
            );
        }

        $content = $candidate['content'] ?? null;

        if (!is_array($content)) {
            throw new AiResponseException(
                'Gemini response did not contain content.',
                provider: 'gemini',
            );
        }

        $parts = $content['parts'] ?? null;

        if (!is_array($parts)) {
            throw new AiResponseException(
                'Gemini response did not contain content parts.',
                provider: 'gemini',
            );
        }

        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }

            $text = $part['text'] ?? null;

            if (is_string($text)) {
                return $text;
            }
        }

        throw new AiResponseException(
            'Gemini response did not contain generated text.',
            provider: 'gemini',
        );
    }
}
