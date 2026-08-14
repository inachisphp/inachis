<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\OpenAiClient;

class OpenAiAudioProvider implements AiAudioProviderInterface
{
    public function __construct(
        private OpenAiClient $client,
        private string $model = 'tts-1',
    ) {}

    public function getName(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function generateSpeech(string $text, string $voice = 'alloy'): string
    {
        $cleanText = strip_tags($text);

        $payload = [
            'input' => $cleanText,
            'voice' => $voice,
            'response_format' => 'mp3',
        ];

        $binaryAudio = $this->client->createSpeech($payload, $this->model);

        if (empty($binaryAudio)) {
            throw new AiResponseException(
                'Failed to generate audio from provider.',
                provider: 'openai',
            );
        }

        return $binaryAudio;
    }
}
