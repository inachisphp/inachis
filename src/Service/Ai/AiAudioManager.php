<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Ai;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Media\Audio;
use Inachis\Enum\Media\AudioStorage;
use Inachis\Repository\Media\AudioRepository;
use Inachis\Service\Ai\Provider\AiAudioProviderInterface;
use Inachis\Service\Resource\ResourceStorageProvider;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class AiAudioManager
{
    /** @var array<string, AiAudioProviderInterface> */
    private array $providers = [];

    private string $activeProviderName;

    /**
     * @param iterable<AiAudioProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.ai_audio_provider')]
        iterable $providers,
        private readonly AudioRepository $audioRepository,
        private readonly ResourceStorageProvider $storageProvider,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%env(default::AI_AUDIO_PROVIDER)%')]
        ?string $audioProviderName = null,
        #[Autowire('%env(default::AI_PROVIDER)%')]
        ?string $defaultProviderName = 'gemini',
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }

        $this->activeProviderName = !empty($audioProviderName)
            ? strtolower($audioProviderName)
            : (!empty($defaultProviderName) ? strtolower($defaultProviderName) : 'gemini');
    }

    public function getActiveProvider(): ?AiAudioProviderInterface
    {
        return $this->providers[$this->activeProviderName] ?? null;
    }

    public function isConfigured(): bool
    {
        $provider = $this->getActiveProvider();

        return null !== $provider && $provider->isConfigured();
    }

    /**
     * Generates or retrieves an existing Audio entity for a given Page/Post.
     */
    public function getOrGeneratePostAudio(Page $post, string $voice = 'george'): Audio
    {
        $provider = $this->getActiveProvider();
        if (null === $provider || !$provider->isConfigured()) {
            throw new \LogicException(
                sprintf('AI Audio Provider "%s" is not registered or configured.', $this->activeProviderName)
            );
        }

        $title = $post->getTitle() ?? 'Untitled';
        $content = $post->getContent() ?? '';
        $fullText = $title . "\n\n" . $content;

        $storageDir = $this->storageProvider->getStorageDirectory(Audio::class);
        $uploadsDir = rtrim(dirname($storageDir), '/') . '/uploads/';

        $stingerPath = $uploadsDir . 'pod_stinger.mp3';
        $trailerPath = $uploadsDir . 'pod_trailer.mp3';

        $hasStinger = file_exists($stingerPath);
        $hasTrailer = file_exists($trailerPath);

        $sourceHash = hash('sha256', $fullText . ($hasStinger ? '1' : '0') . ($hasTrailer ? '1' : '0'));

        // Look for an existing Audio entity matching this source hash
        $existing = $this->audioRepository->findOneBy(['sourceHash' => $sourceHash]);
        if (null !== $existing) {
            return $existing;
        }

        // Generate audio binary via provider
        $postAudioBinary = $provider->generateSpeech($fullText, $voice);

        // Stitch components
        $finalAudioBinary = $this->stitchAudioFiles(
            $postAudioBinary,
            $hasStinger ? $stingerPath : null,
            $hasTrailer ? $trailerPath : null
        );

        $filename = sprintf('post_%s_%s.mp3', (string) $post->getId(), substr($sourceHash, 0, 12));
        $filePath = $storageDir . $filename;

        file_put_contents($filePath, $finalAudioBinary);

        // Create and persist the Audio entity
        $audio = new Audio();
        $audio
            ->setTitle(sprintf('Audio for "%s"', $title))
            ->setFilename($filename)
            ->setFiletype('audio/mpeg')
            ->setFilesize(filesize($filePath) ?: 0)
            ->setChecksum(hash_file('sha256', $filePath) ?: '')
            ->setSourceHash($sourceHash)
            ->setStorage(AudioStorage::LOCAL)
            ->setPage($post)
            ->setAuthor($post->getAuthor());

        $this->entityManager->persist($audio);
        $this->entityManager->flush();

        return $audio;
    }

    private function stitchAudioFiles(
        string $postAudioBinary,
        ?string $stingerPath,
        ?string $trailerPath
    ): string {
        $output = '';

        if ($stingerPath && file_exists($stingerPath)) {
            $output .= file_get_contents($stingerPath);
        }

        $output .= $postAudioBinary;

        if ($trailerPath && file_exists($trailerPath)) {
            $output .= file_get_contents($trailerPath);
        }

        return $output;
    }

    /**
     * Checks whether an Audio entity already exists for the given Page/Post.
     */
    public function hasAudio(Page $post): bool
    {
        if (null === $post->getId()) {
            return false;
        }

        $audio = $this->audioRepository->findOneBy(['page' => $post]);

        return null !== $audio;
    }
}
