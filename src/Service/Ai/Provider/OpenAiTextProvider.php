<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class OpenAiTextProvider implements AiTextProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(OPENAI_API_KEY)%')]
        private ?string $apiKey = null,
    ) {
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function generateText(string $prompt, ?string $systemPrompt = null, bool $jsonMode = false): string
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('OpenAI API key is not configured.');
        }

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'max_tokens' => 1000,
        ];

        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 30,
        ]);

        $data = $response->toArray();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
