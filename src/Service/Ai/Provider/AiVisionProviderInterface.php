<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Inachis\Entity\Media\Image;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_vision_provider')]
interface AiVisionProviderInterface
{
    /**
     * Unique key identifying the provider (e.g. 'gemini', 'openai', 'ollama').
     */
    public function getName(): string;

    /**
     * Checks whether the required API keys or endpoints are configured.
     */
    public function isConfigured(): bool;

    /**
     * Analyzes the given image entity and returns extracted metadata.
     *
     * @return array{title: string, altText: string, description: string}
     */
    public function generateMetadata(Image $image): array;
}
