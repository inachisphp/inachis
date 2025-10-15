<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param Category $category
     */
    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns an array of the root level categories.
     * @return Category[] The array of {@link Category} objects
     */
    public function getRootCategories(): array
    {
        return $this->getRepository()->createQueryBuilder('q')
            ->where('q.parent is null')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $title
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findByTitleLike($title): Paginator
    {
        return $this->getAll(
            0,
            25,
            [
                'q.title LIKE :title',
                [
                    'title' => '%' . $title . '%',
                ],
            ],
            'q.title'
        );
    }
}
