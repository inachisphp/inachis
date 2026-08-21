<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

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

        $base64Data = $this->extractAudioData($response);

        $audioBinary = base64_decode($base64Data, true);

        if (false === $audioBinary) {
            throw new AiResponseException(
                'Failed to decode audio response from Gemini.', 
                provider: 'gemini'
            );
        }

        return $audioBinary;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractAudioData(array $response): string
    {
        $candidates = $response['candidates'] ?? null;
        if (!is_array($candidates) || !isset($candidates[0]) || !is_array($candidates[0])) {
            throw new AiResponseException('Gemini response missing candidate data.', provider: 'gemini');
        }

        $content = $candidates[0]['content'] ?? null;
        if (!is_array($content)) {
            throw new AiResponseException('Gemini response missing content payload.', provider: 'gemini');
        }

        $parts = $content['parts'] ?? null;
        if (!is_array($parts) || !isset($parts[0]) || !is_array($parts[0])) {
            throw new AiResponseException('Gemini response missing parts data.', provider: 'gemini');
        }

        $inlineData = $parts[0]['inlineData'] ?? null;
        if (!is_array($inlineData) || empty($inlineData['data']) || !is_string($inlineData['data'])) {
            throw new AiResponseException('Gemini failed to return synthesized audio data.', provider: 'gemini');
        }

        return $inlineData['data'];
    }
}
