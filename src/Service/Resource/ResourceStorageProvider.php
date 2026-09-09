<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Resource;

use Inachis\Entity\Media\AbstractFile;
use Inachis\Entity\Media\Audio;
use Inachis\Entity\Media\Download;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ResourceStorageProvider
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/imgs')]
        private readonly string $imageDirectory,

        #[Autowire('%kernel.project_dir%/var/uploads')]
        private readonly string $downloadDirectory,

        #[Autowire('%kernel.project_dir%/var/audio')]
        private readonly string $audioDirectory,
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

        if (is_a($type, Audio::class, true) || in_array($type, ['audio'], true)) {
            $dir = $this->audioDirectory;
        } elseif (is_a($type, Download::class, true) || in_array($type, ['downloads', 'download'], true)) {
            $dir = $this->downloadDirectory;
        } else {
            $dir = $this->imageDirectory;
        }

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
