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
class Download extends AbstractFile
{
}
