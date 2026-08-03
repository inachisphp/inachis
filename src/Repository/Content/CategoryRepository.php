<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Inachis\Repository\AbstractRepository;
use Inachis\Repository\Content\CategoryRepositoryInterface;
use Inachis\Entity\Content\Category;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Repository for handling {@link Category} entities
 * 
 * @extends AbstractRepository<Category>
 */
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
     * @return array<int,Category> The array of {@link Category} objects
     */
    public function getRootCategories(): array
    {
        /** @var array<int,Category> */
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
            25,
            0,
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
     * @param integer $limit
     * @param integer $offset
     * @return array<int,Category>
     */
    public function findBatch(
        int $limit,
        int $offset,
    ): array {
        /** @var array<int,Category> */
        return $this->createQueryBuilder('c')
            ->orderBy('c.title', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}
