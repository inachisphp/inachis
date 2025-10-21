<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * Object for handling images on a site.
 */
#[ORM\Entity(repositoryClass: 'App\Repository\DownloadRepository', readOnly: false)]
#[ORM\Index(columns: ['title', 'filename', 'filetype'], name: 'search_idx')]
class Download extends AbstractFile
{
}
