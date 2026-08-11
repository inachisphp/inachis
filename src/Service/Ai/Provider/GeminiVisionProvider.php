<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Inachis\Entity\Media\Image;
use Inachis\Service\Resource\ResourceStorageProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class GeminiVisionProvider implements AiVisionProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ResourceStorageProvider $storageProvider,
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

    public function generateMetadata(Image $image): array
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('Gemini API key is missing.');
        }

        $imagePath = $this->storageProvider->getFullPath($image);
        if (!file_exists($imagePath) || !is_file($imagePath)) {
            throw new \RuntimeException('Image file payload missing on disk.');
        }

        $mimeType = $image->getFiletype() ?: 'image/jpeg';
        $base64Data = base64_encode(file_get_contents($imagePath));

        $prompt = <<<TEXT
Analyze this image and generate concise metadata. 
Respond ONLY with a valid JSON object matching this schema:
{
  "title": "A concise title for the image (3 to 7 words)",
  "altText": "Clear accessibility alt text for screen readers (under 125 characters)",
  "description": "A descriptive caption detailing the scene, mood, or subject"
}
TEXT;

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key='.$this->apiKey;

        $response = $this->httpClient->request('POST', $endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'generationConfig' => ['responseMimeType' => 'application/json'],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Data]],
                        ],
                    ],
                ],
            ],
            'timeout' => 30,
        ]);

        $data = $response->toArray();
        $rawJson = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        /** @var array{title?: string, altText?: string, description?: string} $result */
        $result = json_decode($rawJson, true) ?: [];

        return [
            'title' => $result['title'] ?? $image->getTitle(),
            'altText' => $result['altText'] ?? '',
            'description' => $result['description'] ?? '',
        ];
    }
}
