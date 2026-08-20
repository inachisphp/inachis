<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Inachis\Entity\Media\Image;
use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\GeminiClient;
use Inachis\Service\Resource\ResourceStorageProvider;

readonly class GeminiVisionProvider implements AiVisionProviderInterface
{
    public function __construct(
        private GeminiClient $client,
        private ResourceStorageProvider $storageProvider,
    ) {}

    public function getName(): string
    {
        return 'gemini';
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
        $base64Data = base64_encode($imageContents);

        $prompt = <<<TEXT
Analyze this image and generate concise metadata.

Respond ONLY with a valid JSON object matching this exact schema:
{
  "title": "A concise title for the image (3 to 7 words)",
  "altText": "Clear accessibility alt text for screen readers (under 125 characters)",
  "description": "A descriptive caption detailing the scene, mood, or subject"
}
TEXT;

        $data = $this->client->generateContent([
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64Data,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $rawJson = $this->extractGeneratedText($data);

        $result = json_decode($rawJson, true);

        if (!is_array($result)) {
            throw new AiResponseException(
                'Gemini returned invalid JSON for image metadata.',
                provider: 'gemini',
            );
        }

        return [
            'title' => $this->getStringValue(
                $result,
                'title',
                $image->getTitle(),
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
