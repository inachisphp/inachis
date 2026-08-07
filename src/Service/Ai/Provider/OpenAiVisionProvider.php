<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Inachis\Entity\Media\Image;
use Inachis\Service\Resource\ResourceStorageProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class OpenAiVisionProvider implements AiVisionProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ResourceStorageProvider $storageProvider,
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

    public function generateMetadata(Image $image): array
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('OpenAI API key is missing.');
        }

        $imagePath = $this->storageProvider->getFullPath($image);
        if (!file_exists($imagePath) || !is_file($imagePath)) {
            throw new \RuntimeException('Image file payload missing on disk.');
        }

        $mimeType = $image->getFiletype() ?: 'image/jpeg';
        $dataUrl = sprintf('data:%s;base64,%s', $mimeType, base64_encode(file_get_contents($imagePath)));

        $prompt = <<<TEXT
Analyze this image and return a JSON object with keys:
- "title": A concise title (3 to 7 words).
- "altText": Screen reader accessibility text (under 125 chars).
- "description": Descriptive caption.

Respond ONLY with raw JSON.
TEXT;

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $prompt],
                            ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                        ],
                    ],
                ],
                'max_tokens' => 500,
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray();
        $rawJson = $data['choices'][0]['message']['content'] ?? '{}';
        
        /** @var array{title?: string, altText?: string, description?: string} $result */
        $result = json_decode($rawJson, true) ?: [];

        return [
            'title' => $result['title'] ?? $image->getTitle(),
            'altText' => $result['altText'] ?? '',
            'description' => $result['description'] ?? '',
        ];
    }
}
