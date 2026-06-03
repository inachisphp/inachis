<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Content\Category;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryRepository extends AbstractRepository implements CategoryRepositoryInterface
{
    /**
     * Constructor for CategoryRepository
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Removes a Category entity from the database.
     *
     * @param Category $category
     */
    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns an array of the root level categories.
     *
     * @return Category[] The array of {@link Category} objects
     */
    public function getRootCategories(): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.parent is null')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories by title
     *
     * @param string $title
     * @return Paginator<Category>
     */
    public function findByTitleLike(string $title): Paginator
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

    /**
     * Return a count of visible categories
     *
     * @return integer
     */
    public function countVisibleCategories(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Return a batch of categories, ordered by title, with pagination.
     *
     * @param integer $offset
     * @param integer $limit
     * @return array<Category>
     */
    public function findBatch(
        int $offset,
        int $limit
    ): array {
        return $this->createQueryBuilder('c')
            ->orderBy('c.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
