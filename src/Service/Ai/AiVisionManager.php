<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Ai;

use Inachis\Entity\Media\Image;
use Inachis\Service\Ai\Provider\AiVisionProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class AiVisionManager
{
    /** @var array<string, AiVisionProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<AiVisionProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.ai_vision_provider')]
        iterable $providers,
        #[Autowire('%env(default::AI_PROVIDER)%')]
        private readonly string $activeProviderName = 'gemini',
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    /**
     * Resolves the active provider instance.
     */
    public function getActiveProvider(): ?AiVisionProviderInterface
    {
        // Future Switch: Read from Settings repository here if needed
        // $setting = $this->settingRepository->findOneBy(['name' => 'ai_provider']);
        // $providerName = $setting?->getValue() ?? $this->activeProviderName;

        return $this->providers[$this->activeProviderName] ?? null;
    }

    public function isConfigured(): bool
    {
        $provider = $this->getActiveProvider();

        return null !== $provider && $provider->isConfigured();
    }

    /**
     * Delegates metadata generation to the active AI provider.
     *
     * @return array{title: string, altText: string, description: string}
     */
    public function generateMetadata(Image $image): array
    {
        $provider = $this->getActiveProvider();
        if (!$provider) {
            throw new \LogicException(sprintf('AI Provider "%s" is not registered.', $this->activeProviderName));
        }

        return $provider->generateMetadata($image);
    }
}
