<?php

declare(strict_types=1);

namespace Inachis\Service\Ai\Provider;

use Inachis\Exception\Ai\AiConfigurationException;
use Inachis\Exception\Ai\AiProviderException;
use Inachis\Exception\Ai\AiRateLimitException;
use Inachis\Exception\Ai\AiResponseException;
use Inachis\Exception\Ai\AiTemporaryException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ElevenLabsAudioProvider implements AiAudioProviderInterface
{
    private const string BASE_URL = 'https://api.elevenlabs.io/v1';

    // Default ElevenLabs Model (eleven_turbo_v2_5 is fast, supports SSML break tags)
    private const string DEFAULT_MODEL = 'eleven_turbo_v2_5';

    // Default Voice ID: George (British Male - Baritone / Storyteller)
    private const string DEFAULT_VOICE_ID = 'JBFqnCBsd6RMkjVDRZzb';

    /**
     * Voice alias map to translate friendly names to ElevenLabs Voice IDs.
     */
    private const array VOICE_MAP = [
        'george' => 'JBFqnCBsd6RMkjVDRZzb', // British Male (Storyteller / Baritone)
        'harry'  => 'SOYHLrjzK2X1ezoPC6cr', // British Male (Young / Conversational)
        'callum' => 'N2lVS1w4EtoT3dr4eOWO', // British Male (Casual)
        'rachel' => '21m00Tcm4TlvDq8ikWAM', // American Female
        'adam'   => 'pNInz6obpgDQGcFmaJgB', // American Male
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(ELEVENLABS_API_KEY)%')]
        private readonly ?string $apiKey = null,
        private readonly string $model = self::DEFAULT_MODEL,
    ) {}

    public function getName(): string
    {
        return 'elevenlabs';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Converts text into binary audio data via ElevenLabs API.
     *
     * @param string $text Content to synthesize
     * @param string $voice Voice name alias or raw ElevenLabs Voice ID
     * @return string Raw binary MP3 audio
     */
    public function generateSpeech(string $text, string $voice = 'george'): string
    {
        if (!$this->isConfigured()) {
            throw new AiConfigurationException(
                'ElevenLabs API key is not configured.',
                provider: 'elevenlabs'
            );
        }

        $processedText = $this->prepareTextForSpeech($text);
        if (empty($processedText)) {
            throw new \InvalidArgumentException('Text for speech generation cannot be empty.');
        }

        // Resolve friendly voice name or fallback to George / raw Voice ID passed
        $voiceId = self::VOICE_MAP[strtolower($voice)] ?? ($voice !== 'alloy' ? $voice : self::DEFAULT_VOICE_ID);

        $endpoint = sprintf('%s/text-to-speech/%s', self::BASE_URL, $voiceId);

        $payload = [
            'text' => $processedText,
            'model_id' => $this->model,
            'voice_settings' => [
                'stability' => 0.45,        // Slightly lower stability = more dynamic & conversational
                'similarity_boost' => 0.75, // Keeps character identity strong
                'style' => 0.15,            // Adds extra conversational expression
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'headers' => [
                    'xi-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'audio/mpeg',
                ],
                'json' => $payload,
                'timeout' => 45,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new AiTemporaryException(
                'Unable to communicate with the ElevenLabs API.',
                provider: 'elevenlabs',
                previous: $e
            );
        } catch (\Throwable $e) {
            throw new AiProviderException(
                'An unexpected error occurred while communicating with ElevenLabs.',
                provider: 'elevenlabs',
                previous: $e
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw $this->createException($statusCode, $content);
        }

        if (empty($content)) {
            throw new AiResponseException(
                'ElevenLabs returned an empty audio response.',
                provider: 'elevenlabs'
            );
        }

        return $content; // Raw binary MP3
    }

    /**
     * Prepares text for speech generation by stripping unwanted tags while
     * inserting SSML breaks after headings and block elements for natural pacing.
     */
    private function prepareTextForSpeech(string $rawText): string
    {
        // 1. Replace Markdown headings (# Title) with Title + ElevenLabs SSML Break Tag
        $text = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', function ($matches) {
            $headingText = trim($matches[2]);
            // Adds a 1.2s break after a heading for natural podcast transition
            return sprintf('%s <break time="1.2s" />', $headingText);
        }, $rawText);

        // 2. Strip HTML tags but preserve break tags
        $text = strip_tags($text, '<break>');

        // 3. Normalize multiple blank lines/newlines
        $text = preg_replace('/\n{2,}/', "\n\n", $text);

        return trim($text);
    }

    private function createException(int $statusCode, string $content): \Throwable
    {
        $message = $this->extractErrorMessage($content);

        if (in_array($statusCode, [401, 403], true)) {
            return new AiConfigurationException(
                $message ?? 'ElevenLabs rejected the configured API credentials.',
                provider: 'elevenlabs'
            );
        }

        if (429 === $statusCode) {
            return new AiRateLimitException(
                $message ?? 'ElevenLabs API rate/character limit exceeded.',
                provider: 'elevenlabs'
            );
        }

        if (in_array($statusCode, [408, 500, 502, 503, 504], true)) {
            return new AiTemporaryException(
                $message ?? 'ElevenLabs API is temporarily unavailable.',
                provider: 'elevenlabs'
            );
        }

        return new AiProviderException(
            $message ?? sprintf('ElevenLabs API returned HTTP %d.', $statusCode),
            provider: 'elevenlabs',
            providerStatusCode: $statusCode
        );
    }

    private function extractErrorMessage(string $content): ?string
    {
        if ('' === trim($content)) {
            return null;
        }

        $data = json_decode($content, true);

        return $data['detail']['message'] ?? $data['message'] ?? null;
    }
}
