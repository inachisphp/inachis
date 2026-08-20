<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Media;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Object for handling images on a site.
 */
#[ORM\Entity(repositoryClass: 'Inachis\Repository\Media\DownloadRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
#[ORM\HasLifecycleCallbacks]
class Download extends AbstractFile
{
    /** @var list<string> */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/zip',
        'application/x-tar',
        'application/gzip',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ];

    /** @var list<string> */
    public const ALLOWED_TYPES = [
        '.pdf', '.zip', '.tar', '.gz', '.doc', '.docx', '.xls', '.xlsx', '.txt', '.csv',
    ];

    public const MAX_FILESIZE = 50;

    /** @var Collection<int, DownloadVersion> */
    #[ORM\OneToMany(mappedBy: 'download', targetEntity: DownloadVersion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['versionNumber' => 'DESC'])]
    private Collection $versions;

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
     * @return Collection<int, DownloadVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function getNextVersionNumber(): int
    {
        if ($this->versions->isEmpty()) {
            return 1;
        }

        return $this->versions->first()->getVersionNumber() + 1;
    }

    /**
     * Archives the current file metadata into a DownloadVersion snapshot.
     */
    public function archiveCurrentPayload(): void
    {
        if (empty($this->getFilename())) {
            return;
        }

        $version = new DownloadVersion();
        $version
            ->setDownload($this)
            ->setVersionNumber($this->getNextVersionNumber())
            ->setFilename($this->getFilename())
            ->setFiletype($this->getFiletype() ?? '')
            ->setFilesize($this->getFilesize())
            ->setChecksum($this->getChecksum() ?? '')
            ->setAuthor($this->getAuthor());

        $this->versions->add($version);
    }
}
