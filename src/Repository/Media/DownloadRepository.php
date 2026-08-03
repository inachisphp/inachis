<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Media;

use Inachis\Entity\Media\Download;
use Inachis\Repository\AbstractRepository;
use Inachis\Repository\Media\ResourceRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Download repository for managing Download entities
 * 
 * @extends AbstractRepository<Download>
  * @implements ResourceRepositoryInterface<Download>
 */
class DownloadRepository extends AbstractRepository implements ResourceRepositoryInterface
{
    /** @use DefaultResourceRepository<Download> */
    use DefaultResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Download::class);
    }
}
