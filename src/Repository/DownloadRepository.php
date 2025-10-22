<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Download;
use Doctrine\Persistence\ManagerRegistry;

class DownloadRepository extends AbstractRepository implements ResourceRepositoryInterface
{
    use DefaultResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Download::class);
    }
}
