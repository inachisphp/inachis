<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Entity\User\User;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(readOnly: false)]
#[ORM\Table(name: 'Download_versions')]
class DownloadVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: Download::class, inversedBy: 'versions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Download $download;

    #[ORM\Column(type: 'integer')]
    private int $versionNumber = 1;

    #[Assert\Length(max: 255)]
    #[ORM\Column(type: 'string', nullable: false)]
    protected string $filename = '';

    #[Assert\Length(max: 127)]
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 127, nullable: false)]
    protected string $filetype = '';

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: 'integer')]
    protected int $filesize = 0;

    #[Assert\Length(exactly: 64)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-f0-9]{64}$/i')]
    #[ORM\Column(type: 'string', length: 64, nullable: false)]
    protected string $checksum = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: true)]
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

    public function getDownload(): Download
    {
        return $this->download;
    }

    public function setDownload(Download $download): self
    {
        $this->download = $download;

        return $this;
    }

    public function getVersionNumber(): int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $number): self
    {
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
}
