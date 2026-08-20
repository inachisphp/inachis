<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ai_text_provider')]
interface AiTextProviderInterface
{
    public function getName(): string;

    public function isConfigured(): bool;

    /**
     * Generates a text completion based on a user prompt and system instruction.
     */
    public function generateText(string $prompt, ?string $systemPrompt = null, bool $jsonMode = false): string;
}
