<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Inachis\Entity\Media\Image;
use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\OpenAiClient;
use Inachis\Service\Resource\ResourceStorageProvider;

readonly class OpenAiVisionProvider implements AiVisionProviderInterface
{
    public function __construct(
        private OpenAiClient $client,
        private ResourceStorageProvider $storageProvider,
    ) {}

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array{title: string, altText: string, description: string}
     */
    public function generateMetadata(Image $image): array
    {
        $imagePath = $this->storageProvider->getFullPath($image);

        if (!is_file($imagePath) || !is_readable($imagePath)) {
            throw new \RuntimeException(
                'Image file payload is missing or cannot be read.',
            );
        }

        $imageContents = file_get_contents($imagePath);

        if (false === $imageContents) {
            throw new \RuntimeException(
                'Unable to read image file payload.',
            );
        }

        $mimeType = $image->getFiletype() ?: 'image/jpeg';

        $dataUrl = sprintf(
            'data:%s;base64,%s',
            $mimeType,
            base64_encode($imageContents),
        );

        $prompt = <<<TEXT
Analyze this image and return a JSON object with these keys:
- "title": A concise title (3 to 7 words).
- "altText": Screen reader accessibility text (under 125 chars).
- "description": Descriptive caption.

Respond ONLY with raw JSON.
TEXT;

        $data = $this->client->createChatCompletion([
            'response_format' => [
                'type' => 'json_object',
            ],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $dataUrl,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 500,
        ]);

        $rawJson = $this->extractGeneratedText($data);

        $result = json_decode($rawJson, true);

        if (!is_array($result)) {
            throw new AiResponseException(
                'OpenAI returned invalid JSON for image metadata.',
                provider: 'openai',
            );
        }

        /** @var array<string, mixed> $result */
        return [
            'title' => $this->getStringValue(
                $result,
                'title',
                $image->getTitle() ?? '',
            ),
            'altText' => $this->getStringValue(
                $result,
                'altText',
            ),
            'description' => $this->getStringValue(
                $result,
                'description',
            ),
        ];
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

    /**
     * @param array<string, mixed> $data
     */
    private function getStringValue(
        array $data,
        string $key,
        string $default = '',
    ): string {
        $value = $data[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }
}
