<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Url;
use Doctrine\Common\Persistence\ManagerRegistry;

final class PageRepository extends AbstractRepository
{
    /**
     * The maximum number of items to show in the admin interface
     */
    const MAX_ITEMS_TO_SHOW_ADMIN = 10;

    /**
     * PageRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * @param Page $page
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Page $page)
    {
        foreach ($page->getUrls() as $postUrl) {
            $this->getEntityManager()->getRepository(Url::class)->remove($postUrl);
        }
        // @todo are series links automatically removed? assume not
        $this->getEntityManager()->remove($page);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Category $category
     * @return mixed
     */
    public function getPagesWithCategory(Category $category)
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.categories', 'Page_categories')
            ->where('Page_categories.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->getQuery()
            ->execute();
    }

    /**
     * @param $type
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getAllOfTypeByPostDate($type, $offset, $limit)
    {
        return $this->getFilteredOfTypeByPostDate([], $type, $offset, $limit);
    }

    /**
     * @param $filters
     * @param $type
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFilteredOfTypeByPostDate($filters, $type, $offset, $limit)
    {
        $where = [
            'q.type = :type',
            array_merge(
                [
                    'type' => $type,
                ],
                $filters
            )
        ];
        if (!empty($filters['status'])) {
            $where[0] .= ' AND q.status = :status';
        }
        if (!empty($filters['visibility'])) {
            $where[0] .= ' AND q.visibility = :visibility';
        }
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.content LIKE :keyword )';
            $where[1]['keyword'] = '%' . $where[1]['keyword'] . '%';
        }
        return $this->getAll(
            $offset,
            $limit,
            $where,
            [
                [ 'q.postDate', 'DESC' ],
                [ 'q.modDate', 'DESC' ]
            ]
        );
    }

    /**
     * @param $ids
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFilteredIds($ids)
    {
        return $this->getAll(
            0,
            0,
            [
                'q.id IN (:ids)',
                [
                    'ids' => $ids,
                ]
            ]
        );
    }
}
