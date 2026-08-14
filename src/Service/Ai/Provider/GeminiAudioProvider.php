<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Inachis\Exception\Ai\AiResponseException;
use Inachis\Service\Ai\Client\GeminiClient;

class GeminiAudioProvider implements AiAudioProviderInterface
{
    private const VOICE_MAP = [
        'alloy'   => 'Puck',
        'echo'    => 'Fenrir',
        'fable'   => 'Charon',
        'onyx'    => 'Kore',
        'nova'    => 'Aoede',
        'shimmer' => 'Zephyr',
    ];

    public function __construct(
        private GeminiClient $client,
        private string $model = 'gemini-3.1-flash',
    ) {}

    public function getName(): string
    {
        return 'gemini';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function generateSpeech(string $text, string $voice = 'Puck'): string
    {
        $cleanText = strip_tags($text);

        $selectedVoice = self::VOICE_MAP[strtolower($voice)] ?? $voice;

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are a Text-to-Speech (TTS) audio synthesizer. Read the provided text aloud exactly as written into spoken audio without altering, summarizing, or adding editorial commentary. Use an engaging tone.']
                ]
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $cleanText],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => [
                    'voiceConfig' => [
                        'prebuiltVoiceConfig' => [
                            'voiceName' => $selectedVoice,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->client->generateContent($payload, $this->model);

        $part = $response['candidates'][0]['content']['parts'][0]['inlineData'] ?? null;

        if (empty($part['data'])) {
            throw new AiResponseException(
                'Gemini failed to return synthesized audio data.',
                provider: 'gemini',
            );
        }

        $audioBinary = base64_decode($part['data'], true);

        if (false === $audioBinary) {
            throw new AiResponseException(
                'Failed to decode audio response from Gemini.', 
                provider: 'gemini'
            );        }

        return $audioBinary;
    }
}
