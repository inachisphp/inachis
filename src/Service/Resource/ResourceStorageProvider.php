<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Resource;

use Inachis\Entity\Media\AbstractFile;
use Inachis\Entity\Media\Download;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ResourceStorageProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/imgs')]
        private readonly string $imageDirectory,

        #[Autowire('%kernel.project_dir%/var/uploads')]
        private readonly string $downloadDirectory,
    ) {
    }

    /**
     * Resolves the storage directory for an entity or type string with a trailing slash.
     */
    public function getStorageDirectory(AbstractFile|string $resource): string
    {
        $type = $resource instanceof AbstractFile
            ? $resource::class
            : $resource;

        $dir = is_a($type, Download::class, true) || in_array($type, ['downloads', 'download'], true)
            ? $this->downloadDirectory
            : $this->imageDirectory;

        return rtrim($dir, '/\\').'/';
    }

    /**
     * Resolves the full absolute system path for a given file entity.
     */
    public function getFullPath(AbstractFile $file): string
    {
        return $this->getStorageDirectory($file).$file->getFilename();
    }
}
