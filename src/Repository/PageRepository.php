<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Tag;
use App\Entity\Url;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for retrieving {@link Page} entities
 */
final class PageRepository extends AbstractRepository implements PageRepositoryInterface
{
    /**
     * The maximum number of items to show in the admin interface
     */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 10;

    /**
     * PageRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * @param Page $page The {@link Page} entity to be removed.
     * @return void
     */
    public function remove(Page $page): void
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
     * @param int $maxDisplayCount
     * @param int $offset
     * @return mixed
     */
    public function getPagesWithCategory(Category $category, int $maxDisplayCount = null, int $offset = 0)
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('p')
            ->leftJoin('p.categories', 'Page_categories')
            ->where(
                $qb->expr()->andX(
                    'Page_categories.id = :categoryId',
                    'p.status = \'published\'',
                    'p.visibility = \'1\'',
                    'p.type = \'post\''
                )
            )
            ->orderBy('p.postDate', 'DESC')
            ->setParameter('categoryId', $category->getId());
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        return $qb
            ->setMaxResults($maxDisplayCount)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Category $category
     * @return int
     */
    public function getPagesWithCategoryCount(Category $category): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p) AS numPages')
            ->leftJoin('p.categories', 'Page_categories')
            ->andWhere('Page_categories.id = :categoryId')
            ->setParameter('categoryId', $category);
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Tag $tag
     * @param int $maxDisplayCount
     * @param int $offset
     * @return mixed
     */
    public function getPagesWithTag(Tag $tag, int $maxDisplayCount = null, int $offset = 0)
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('p')
            ->leftJoin('p.tags', 'Page_tags')
            ->where(
                $qb->expr()->andX(
                    'Page_tags.id = :tagId',
                    'p.status = \'published\'',
                    'p.visibility = \'1\'',
                    'p.type = \'post\''
                )
            )
            ->orderBy('p.postDate', 'DESC')
            ->setParameter('tagId', $tag->getId());
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        return $qb
            ->setMaxResults($maxDisplayCount)
            ->getQuery()
            ->execute();
    }

    /**
     * @param $type
     * @param $offset
     * @param $limit
     * @return Paginator
     */
    public function getAllOfTypeByPostDate($type, $offset, $limit): Paginator
    {
        return $this->getFilteredOfTypeByPostDate([], $type, $offset, $limit);
    }

    /**
     * @param $filters
     * @param string $type
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator
     */
    public function getFilteredOfTypeByPostDate(
        $filters,
        string $type,
        int $offset,
        int $limit,
        string $sort = 'postDate desc'
    ): Paginator {
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
        switch ($sort) {
            case 'title asc':
                $sort = [
                    [ 'q.title', 'ASC' ],
                    [ 'q.subTitle', 'ASC' ],
                ];
                break;
            case 'title desc':
                $sort = [
                    [ 'q.title', 'DESC' ],
                    [ 'q.subTitle', 'DESC' ],
                ];
                break;
            case 'modDate asc':
                $sort = [[ 'q.modDate', 'ASC' ]];
                break;
            case 'modDate desc':
                $sort = [[ 'q.modDate', 'DESC' ]];
                break;
            case 'postDate asc':
                $sort = [[ 'q.postDate', 'ASC' ]];
                break;
            case 'postDate desc':
            default:
                $sort = [[ 'q.postDate', 'DESC' ]];
        }
        return $this->getAll(
            $offset,
            $limit,
            $where,
            $sort,
        );
    }

    /**
     * @param $ids
     * @return Paginator
     */
    public function getFilteredIds($ids): Paginator
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

    /**
     * @param Image $image
     * @return Paginator
     */
    public function getPostsUsingImage(Image $image): Paginator
    {
        return $this->getAll(
            0,
            25,
            [
                'q.content LIKE :filename OR q.featureImage = :image',
                [
                    'filename' => '%' . $image->getFilename() . '%',
                    'image' => $image->getId(),
                ]
            ]
        );
    }
}
