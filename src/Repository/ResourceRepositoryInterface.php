<?php

namespace App\Repository;

use App\Entity\AbstractFile;
use Doctrine\ORM\Tools\Pagination\Paginator;

interface ResourceRepositoryInterface
{
    public function remove(AbstractFile $download): void;
    public function getFiltered($filters, $offset, $limit, ?string $sortBy = 'title asc'): Paginator;
}

