<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Enum\Media\AudioStorage;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a historical version of an audio resource.
 *
 * An audio version is an immutable snapshot of the audio resource at a
 * particular point in time. The current Audio entity retains the latest
 * version's metadata.
 */
#[ORM\Entity(readOnly: false)]
#[ORM\Table(name: 'Audio_versions')]
#[ORM\UniqueConstraint(
    name: 'audio_version_number_unique',
    columns: ['audio_id', 'version_number'],
)]
class AudioVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Audio::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(
        name: 'audio_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private Audio $audio;

    #[Assert\Positive]
    #[ORM\Column(type: 'integer')]
    private int $versionNumber = 1;

    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $filename = '';

    #[Assert\Length(max: 127)]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 127, nullable: false)]
    private string $filetype = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer')]
    private int $filesize = 0;

    #[Assert\Length(exactly: 64)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-f0-9]{64}$/i')]
    #[ORM\Column(type: 'string', length: 64, nullable: false)]
    private string $checksum = '';

    /**
     * Duration of the audio in seconds.
     */
    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    /**
     * Indicates where this version of the audio is stored.
     */
    #[ORM\Column(
        type: 'string',
        enumType: AudioStorage::class,
        length: 20,
    )]
    private AudioStorage $storage = AudioStorage::LOCAL;

    /**
     * URL for audio hosted externally.
     *
     * This is used when {@see $storage} is {@see AudioStorage::EXTERNAL}.
     */
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    #[ORM\Column(type: 'string', length: 2048, nullable: true)]
    private ?string $externalUrl = null;

    /**
     * Hash of the source content used to generate this version.
     *
     * For AI-generated audio, this can be used to identify the exact
     * content from which the audio was generated.
     */
    #[Assert\Length(exactly: 64)]
    #[Assert\Regex('/^[a-f0-9]{64}$/i')]
    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $sourceHash = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'author_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $author = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getAudio(): Audio
    {
        return $this->audio;
    }

    public function setAudio(Audio $audio): self
    {
        $this->audio = $audio;

        return $this;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $number): self
    {
        if ($number < 1) {
            throw new \InvalidArgumentException(
                'Audio version number must be greater than zero.',
            );
        }

        $this->versionNumber = $number;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFiletype(): string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): self
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    public function setFilesize(int $filesize): self
    {
        if ($filesize < 0) {
            throw new \InvalidArgumentException(
                'Audio file size must be a positive integer.',
            );
        }

        $this->filesize = $filesize;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        if (null !== $duration && $duration < 0) {
            throw new \InvalidArgumentException(
                'Audio duration must be a positive integer or null.',
            );
        }

        $this->duration = $duration;

        return $this;
    }

    public function getStorage(): AudioStorage
    {
        return $this->storage;
    }

    public function setStorage(AudioStorage $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): self
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function getSourceHash(): ?string
    {
        return $this->sourceHash;
    }

    public function setSourceHash(?string $sourceHash): self
    {
        $this->sourceHash = $sourceHash;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Determines whether this version is externally hosted.
     */
    public function isExternal(): bool
    {
        return AudioStorage::EXTERNAL === $this->storage;
    }

    /**
     * Determines whether this version is stored locally.
     */
    public function isLocal(): bool
    {
        return AudioStorage::LOCAL === $this->storage;
    }
}
