<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_audio_provider')]
interface AiAudioProviderInterface
{
    public function getName(): string;

    public function isConfigured(): bool;

    /**
     * Converts text into binary audio data (e.g. MP3).
     *
     * @param string $text Content to synthesize
     * @param string $voice Voice profile (e.g., 'alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer')
     * @return string Raw binary audio output
     */
    public function generateSpeech(string $text, string $voice = 'alloy'): string;
}
