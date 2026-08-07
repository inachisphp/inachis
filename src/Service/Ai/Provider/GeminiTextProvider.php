<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class GeminiTextProvider implements AiTextProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(GEMINI_API_KEY)%')]
        private ?string $apiKey = null,
    ) {
    }

    public function getName(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generateText(string $prompt, ?string $systemPrompt = null, bool $jsonMode = false): string
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('Gemini API key is not configured.');
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        if ($systemPrompt) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ];
        }

        if ($jsonMode) {
            $payload['generationConfig'] = [
                'responseMimeType' => 'application/json',
            ];
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $this->apiKey;

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $payload,
            'timeout' => 30,
        ]);

        $data = $response->toArray();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}
