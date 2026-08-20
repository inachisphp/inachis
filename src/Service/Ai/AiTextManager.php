<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai;

use Inachis\Service\Ai\Provider\AiTextProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class AiTextManager
{
    /** @var array<string, AiTextProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<AiTextProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.ai_text_provider')]
        iterable $providers,
        #[Autowire('%env(default::AI_PROVIDER)%')]
        private readonly string $activeProviderName = 'gemini',
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function getActiveProvider(): ?AiTextProviderInterface
    {
        return $this->providers[$this->activeProviderName] ?? null;
    }

    public function isConfigured(): bool
    {
        $provider = $this->getActiveProvider();

        return null !== $provider && $provider->isConfigured();
    }

    public function generateText(string $prompt, ?string $systemPrompt = null, bool $jsonMode = false): string
    {
        $provider = $this->getActiveProvider();
        if (!$provider) {
            throw new \LogicException(sprintf('AI Provider "%s" is not registered or configured.', $this->activeProviderName));
        }

        return $provider->generateText($prompt, $systemPrompt, $jsonMode);
    }

    /**
     * Helper to generate SEO metadata from post content.
     *
     * @return array{excerpt: string, metaDescription: string, keywords: string[]}
     */
    public function generateSeoMetadata(string $postContent, ?string $title = null): array
    {
        $cleanContent = trim(strip_tags($postContent));
        if (empty($cleanContent)) {
            throw new \InvalidArgumentException('Post content cannot be empty.');
        }

        $systemPrompt = <<<TEXT
You are an expert SEO editor and content strategist.
Analyze the provided blog post content (and optional title) and generate SEO metadata.
Respond ONLY with a valid JSON object matching this exact schema:
{
  "excerpt": "A compelling 1-2 sentence summary of the post (150-200 characters max) for blog index cards.",
  "metaDescription": "A search engine optimized meta description (140-160 characters max) with a clear call to action.",
  "keywords": ["keyword1", "keyword2", "keyword3", "keyword4", "keyword5"]
}
TEXT;

        $prompt = sprintf("Title: %s\n\nContent:\n%s", $title ?: 'Untitled', mb_substr($cleanContent, 0, 8000));

        $jsonString = $this->generateText($prompt, $systemPrompt, true);

        /** @var array{excerpt?: string, metaDescription?: string, keywords?: string[]} $result */
        $result = json_decode($jsonString, true) ?: [];

        return [
            'excerpt' => $result['excerpt'] ?? '',
            'metaDescription' => $result['metaDescription'] ?? '',
            'keywords' => $result['keywords'] ?? [],
        ];
    }
}
