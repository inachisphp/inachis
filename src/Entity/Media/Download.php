<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Media;

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
        '.pdf', '.zip', '.tar', '.gz', '.doc', '.docx', '.xls', '.xlsx', '.txt', '.csv'
    ];
    
    public const MAX_FILESIZE = 50;

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
}
