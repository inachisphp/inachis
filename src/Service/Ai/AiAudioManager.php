<?php

declare(strict_types=1);

namespace Inachis\Service\Ai;

use Inachis\Service\Ai\Provider\AiAudioProviderInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class AiAudioManager
{
    /** @var array<string, AiAudioProviderInterface> */
    private array $providers = [];

    private string $activeProviderName;
    private string $storageDir;
    private string $uploadsDir;

    /**
     * @param iterable<AiAudioProviderInterface> $providers
     */
    public function __construct(
        #[AutowireIterator('app.ai_audio_provider')]
        iterable $providers,
        #[Autowire('%env(default::AI_AUDIO_PROVIDER)%')]
        ?string $audioProviderName = null,
        #[Autowire('%env(default::AI_PROVIDER)%')]
        ?string $defaultProviderName = 'gemini',
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDirectory = '',
        string $relativeAudioDir = 'var/audio/',
        string $relativeUploadsDir = 'var/uploads/',
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }

        // 1. Use AI_AUDIO_PROVIDER if defined
        // 2. Fall back to AI_PROVIDER
        // 3. Fall back to 'gemini'
        $this->activeProviderName = !empty($audioProviderName) 
            ? strtolower($audioProviderName) 
            : (!empty($defaultProviderName) ? strtolower($defaultProviderName) : 'gemini');

        $this->storageDir = rtrim($this->projectDirectory, '/') . '/' . trim($relativeAudioDir, '/') . '/';
        $this->uploadsDir = rtrim($this->projectDirectory, '/') . '/' . trim($relativeUploadsDir, '/') . '/';

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
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
     * Generates or retrieves cached MP3 file path for a given Page/Post.
     */
    public function getOrGeneratePostAudio(
        UuidInterface|string $postId, 
        string $title, 
        string $content, 
        string $voice = 'alloy'
    ): array {
        $provider = $this->getActiveProvider();
        if (null === $provider || !$provider->isConfigured()) {
            throw new \LogicException(sprintf('AI Audio Provider "%s" is not registered or configured.', $this->activeProviderName));
        }

        $idString = (string) $postId;
        
        $stingerPath = $this->uploadsDir . 'pod_stinger.mp3';
        $trailerPath = $this->uploadsDir . 'pod_trailer.mp3';

        $hasStinger = file_exists($stingerPath);
        $hasTrailer = file_exists($trailerPath);

        $fullText = $title . "\n\n" . $content;
        $contentHash = md5($fullText . ($hasStinger ? '1' : '0') . ($hasTrailer ? '1' : '0'));
        
        $filename = sprintf('post_%s_%s.mp3', $idString, $contentHash);
        $filePath = $this->storageDir . $filename;

        // --- CACHE HIT ---
        if (file_exists($filePath)) {
            return [
                'success'  => true,
                'cached'   => true,
                'filePath' => $filePath,
                'hash'     => $contentHash,
            ];
        }

        // --- CACHE MISS ---
        $this->purgeOldPostAudio($idString);

        // 1. Generate core post audio binary via active provider
        $postAudioBinary = $provider->generateSpeech($fullText, $voice);

        // 2. Stitch Stinger + Post Audio + Trailer together
        $finalAudioBinary = $this->stitchAudioFiles(
            $postAudioBinary,
            $hasStinger ? $stingerPath : null,
            $hasTrailer ? $trailerPath : null
        );

        file_put_contents($filePath, $finalAudioBinary);

        return [
            'success'  => true,
            'cached'   => false,
            'filePath' => $filePath,
            'hash'     => $contentHash,
        ];
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

    public function getAudioFilePath(UuidInterface|string $postId): ?string
    {
        $idString = (string) $postId;
        $pattern = $this->storageDir . sprintf('post_%s_*.mp3', $idString);
        $files = glob($pattern);

        return !empty($files) ? $files[0] : null;
    }

    private function purgeOldPostAudio(string $idString): void
    {
        $pattern = $this->storageDir . sprintf('post_%s_*.mp3', $idString);
        foreach (glob($pattern) as $oldFile) {
            if (is_file($oldFile)) {
                unlink($oldFile);
            }
        }
    }
}
