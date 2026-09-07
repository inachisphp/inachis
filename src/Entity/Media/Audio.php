<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Entity\Media;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\Content\Page;
use Inachis\Enum\Media\AudioStorage;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Object for handling audio files associated with content.
 *
 * @phpstan-type AudioShape array{
 *    id: string,
 *    title?: string,
 *    description?: string,
 *    filename: string,
 *    filetype: string,
 *    filesize: int,
 *    checksum: string,
 *    author?: string,
 *    createdAt: string,
 *    updatedAt: string,
 *    duration?: int,
 *    storage: AudioStorage,
 *    externalUrl?: string,
 *    sourceHash?: string
 * }
 */
#[ORM\Entity(
    repositoryClass: 'Inachis\Repository\Media\AudioRepository',
    readOnly: false,
)]
#[ORM\Index(
    columns: ['title', 'filename', 'filetype'],
    name: 'search_idx',
)]
#[ORM\HasLifecycleCallbacks]
class Audio extends AbstractFile
{
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = [
        'audio/mpeg',
        'audio/mp3',
        'audio/mp4',
        'audio/aac',
        'audio/ogg',
        'audio/opus',
        'audio/wav',
        'audio/webm',
        'audio/x-m4a',
        'audio/x-wav',
    ];

    /** @var list<string> */
    public const ALLOWED_TYPES = [
        '.mp3',
        '.m4a',
        '.mp4',
        '.aac',
        '.ogg',
        '.opus',
        '.wav',
        '.webm',
    ];

    /**
     * Duration of the audio in seconds.
     */
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $duration = null;

    /**
     * The page or post this audio is associated with.
     */
    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(
        name: 'page_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    protected ?Page $page = null;

    /**
     * Indicates where the audio is stored.
     */
    #[ORM\Column(
        type: 'string',
        enumType: AudioStorage::class,
        length: 20,
    )]
    protected AudioStorage $storage = AudioStorage::LOCAL;

    /**
     * URL for audio hosted externally.
     *
     * This is used when {@see $storage} is {@see AudioStorage::EXTERNAL}.
     */
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    #[ORM\Column(type: 'string', length: 2048, nullable: true)]
    protected ?string $externalUrl = null;

    /**
     * Hash of the source content used to generate the audio.
     *
     * For AI-generated audio this allows the generated resource to be
     * associated with the version of the source content from which it
     * was produced.
     */
    #[Assert\Length(exactly: 64)]
    #[Assert\Regex('/^[a-f0-9]{64}$/i')]
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $sourceHash = null;

    /** @var Collection<int, AudioVersion> */
    #[ORM\OneToMany(
        mappedBy: 'audio',
        targetEntity: AudioVersion::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['versionNumber' => 'DESC'])]
    protected Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Returns the duration of the audio in seconds.
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * Sets the duration of the audio in seconds.
     */
    public function setDuration(?int $value): self
    {
        if (null !== $value && $value < 0) {
            throw new \InvalidArgumentException(
                'Audio duration must be a positive integer or null.',
            );
        }

        $this->duration = $value;

        return $this;
    }

    /**
     * Returns the page associated with the audio.
     */
    public function getPage(): ?Page
    {
        return $this->page;
    }

    /**
     * Sets the page associated with the audio.
     */
    public function setPage(?Page $value): self
    {
        $this->page = $value;

        return $this;
    }

    /**
     * Returns where the audio is stored.
     */
    public function getStorage(): AudioStorage
    {
        return $this->storage;
    }

    /**
     * Sets where the audio is stored.
     */
    public function setStorage(AudioStorage $value): self
    {
        $this->storage = $value;

        return $this;
    }

    /**
     * Returns the externally hosted audio URL.
     */
    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    /**
     * Sets the externally hosted audio URL.
     */
    public function setExternalUrl(?string $value): self
    {
        $this->externalUrl = $value;

        return $this;
    }

    /**
     * Returns the source content hash used to generate the audio.
     */
    public function getSourceHash(): ?string
    {
        return $this->sourceHash;
    }

    /**
     * Sets the source content hash used to generate the audio.
     */
    public function setSourceHash(?string $value): self
    {
        $this->sourceHash = $value;

        return $this;
    }

    /**
     * Returns the versions of this audio resource.
     *
     * @return Collection<int, AudioVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    /**
     * Returns the current version of this audio resource.
     */
    public function getCurrentVersion(): ?AudioVersion
    {
        return $this->versions->first() ?: null;
    }

    /**
     * Returns the next available version number.
     */
    public function getNextVersionNumber(): int
    {
        $currentVersion = $this->getCurrentVersion();

        return null === $currentVersion
            ? 1
            : $currentVersion->getVersionNumber() + 1;
    }

    /**
     * Adds an audio version.
     */
    public function addVersion(AudioVersion $version): self
    {
        if (!$this->versions->contains($version)) {
            $this->versions->add($version);
        }

        if ($version->getAudio() !== $this) {
            $version->setAudio($this);
        }

        return $this;
    }

    /**
     * Removes an audio version.
     */
    public function removeVersion(AudioVersion $version): self
    {
        $this->versions->removeElement($version);

        return $this;
    }

    /**
     * Determines whether the audio is externally hosted.
     */
    public function isExternal(): bool
    {
        return AudioStorage::EXTERNAL === $this->storage;
    }

    /**
     * Determines whether the audio is stored locally.
     */
    public function isLocal(): bool
    {
        return AudioStorage::LOCAL === $this->storage;
    }
}
