<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param Category $category
     */
    public function remove(Category $category)
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

    /**
     * Returns an array of the root level categories.
     * @return Category[] The array of {@link Category} objects
     */
    public function getRootCategories()
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
    public function findByTitleLike($title)
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
