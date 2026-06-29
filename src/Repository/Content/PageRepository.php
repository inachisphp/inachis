<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Inachis\Entity\Content\{Category, Page, Tag};
use Inachis\Entity\Media\Image;
use Inachis\Repository\AbstractRepository;
use Inachis\Repository\Content\PageRepositoryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Enum\EditorialStatus;

/**
 * Repository for retrieving {@link Page} entities
 *
 * @extends AbstractRepository<Page>
 */
class PageRepository extends AbstractRepository implements PageRepositoryInterface
{
    /**
     * The maximum number of items to show in the admin interface
     */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 10;

    /**
     * PageRepository constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * Remove the given page from the database
     *
     * @param Page $page The {@link Page} entity to be removed.
     * @return void
     */
    public function remove(Page $page): void
    {
        $this->getEntityManager()->remove($page);
        $this->getEntityManager()->flush();
    }

    /**
     * Get all pages with the given category
     *
     * @param Category $category
     * @param int $limit
     * @param int $offset
     * @return array<Page>
     */
    public function getPagesWithCategory(Category $category, int $limit = 0, int $offset = 0)
    {
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate >= :now)')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.type = :type')
            ->setParameter('categoryId', $category->getId(), 'uuid_binary')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->setParameter('visible', true)
            ->setParameter('type', Page::TYPE_POST)
            ->orderBy('p.postDate', 'DESC');
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        if ($limit > 0) {
            $qb = $qb->setMaxResults($limit);
        }
        /** @var array<Page> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the number of pages with the given category
     *
     * @param Category $category
     * @return int
     */
    public function getPagesWithCategoryCount(Category $category): int
    {
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate >= :now)')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.type = :type')
            ->setParameter('categoryId', $category->getId())
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->setParameter('visible', true)
            ->setParameter('type', Page::TYPE_POST);
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages with the given tag
     *
     * @param Tag $tag
     * @param int $maxDisplayCount
     * @param int $offset
     * @return array<Page>
     */
    public function getPagesWithTag(Tag $tag, int $maxDisplayCount = 0, int $offset = 0): array
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.tags', 't')
            ->where('t.id = :tagId')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate >= :now OR p.expireDate IS NULL)')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.type = :type')
            ->setParameter('tagId', $tag->getId(), 'uuid_binary')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->setParameter('visible', true)
            ->setParameter('type', Page::TYPE_POST)
            ->orderBy('p.postDate', 'DESC');

        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        if ($maxDisplayCount > 0) {
            $qb = $qb->setMaxResults($maxDisplayCount);
        }
        /** @var array<Page> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the number of pages with the given tag
     *
     * @param Tag $tag
     * @return int
     */
    public function getPagesWithTagCount(Tag $tag): int
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p) AS numPages')
            ->leftJoin('p.tags', 'Page_tags')
            ->where('t.id = :tagId')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate >= :now OR p.expireDate IS NULL)')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.type = :type')
            ->setParameter('tagId', $tag->getId(), 'uuid_binary')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->setParameter('visible', true)
            ->setParameter('type', Page::TYPE_POST);

        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all content of a certain type, ordered by post date
     *
     * @param string $type
     * @param int $offset
     * @param int $limit
     * @return Paginator<Page>
     */
    public function getAllOfTypeByPostDate(string $type, int $offset, int $limit): Paginator
    {
        return $this->getFilteredOfTypeByPostDate([], $type, $offset, $limit);
    }

    /**
     * Determine the order by clause for the query builder
     *
     * @param string $orderBy
     * @return list<list{0: string, 1: string}>
     */
    protected function determineOrderBy(string $orderBy): array
    {
        return match ($orderBy) {
            'title asc' => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'updatedAt asc' => [['q.updatedAt', 'ASC']],
            'updatedAt desc' => [['q.updatedAt', 'DESC']],
            'postDate asc' => [['q.postDate', 'ASC']],
            default => [['q.postDate', 'DESC']],
        };
    }

    /**
     * Get all content of a certain type, ordered by post date
     *
     * @param array{
     *   categories?:array<string>,
     *   tags?:array<string>,
     *   status?:string,
     *   visible?:bool,
     *   keyword?:string,
     *   excludeIds?:list<string>,
     *   fromDate?:\DateTimeImmutable,
     *   toDate?:\DateTimeImmutable
     * } $filters
     * @param string $type
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator<Page>
     */
    public function getFilteredOfTypeByPostDate(
        array $filters,
        string $type,
        int $offset,
        int $limit,
        string $sort = 'postDate desc'
    ): Paginator {
        $join = [];
        if (isset($filters['categories']) && empty($filters['categories'])) {
            unset($filters['categories']);
        }
        if (isset($filters['tags']) && empty($filters['tags'])) {
            unset($filters['tags']);
        }
        $where = [
            '1=1',
            [],
        ];
        if ($type != '*') {
            $where = [
                'q.type = :type',
                array_merge(
                    [
                        'type' => $type,
                    ],
                    []
                )
            ];
        }
        if (!empty($filters['categories'])) {
            $where[0] .= ' AND c.id IN (:categories)';
            $where[1]['categories'] = [
                'value' => implode(',', array_map(
                    fn($t) => $t->toString(),
                    array_is_list($filters['categories']) ? $filters['categories'] : array_keys($filters['categories'])
                )),
                'type' => 'uuid_binary',
            ];
            $join[] = ['leftJoin', 'q.categories', 'c'];
        }
        if (!empty($filters['tags'])) {
            $where[0] .= ' AND t.id IN (:tags)';
            $where[1]['tags'] = [
                'value' => implode(',', array_map(
                    fn($t) => $t?->toString(),
                    array_is_list($filters['tags']) ? $filters['tags'] : array_keys($filters['tags'])
                )),
                'type' => 'uuid_binary',
            ];
            $join[] = ['leftJoin', 'q.tags', 't'];
        }
        if (!empty($filters['status'])) {
            $where[0] .= ' AND q.status = :status';
            $where[1]['status'] = $filters['status'];
        }
        if (!empty($filters['visible'])) {
            $where[0] .= ' AND q.visible = :visible';
            $where[1]['visible'] = $filters['visible'];
        }
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.content LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword'] . '%';
        }
        if (!empty($filters['excludeIds'])) {
            $where[0] .= ' AND q.id NOT IN (:excludeIds)';
            $where[1]['excludeIds'] = $filters['excludeIds'];
        }
        if (!empty($filters['fromDate'])) {
            $where[0] .= ' AND q.postDate >= :fromDate';
            $where[1]['fromDate'] = $filters['fromDate'];
        }
        if (!empty($filters['toDate'])) {
            $where[0] .= ' AND q.postDate <= :toDate';
            $where[1]['toDate'] = $filters['toDate'];
        }

        return $this->getAll(
            $offset,
            $limit,
            $where,
            $this->determineOrderBy($sort),
            [],
            $join
        );
    }

    /**
     * Get all pages with the given ids
     *
     * @param list<string> $ids
     * @return Paginator<Page>
     */
    public function getFilteredIds(array $ids): Paginator
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
     * Get all pages that use the given image
     *
     * @param Image $image
     * @return Paginator<Page>
     */
    public function getPostsUsingImage(Image $image): Paginator
    {
        return $this->getAll(
            0,
            25,
            [
                'q.content LIKE :filename OR q.featureImage = :image',
                [
                    'filename' => '%' . $image->getFilename(). '%',
                    'image' => $image->getId()?->toString() ?? '',
                ]
            ]
        );
    }

    /**
     * Get the top N pages with the largest image size calculated
     *
     * @param int $limit
     * @return array<Page>
     */
    public function getTopPagesByImageSize(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('p')
            ->orderBy('p.imageSize', 'DESC')
            ->setMaxResults($limit);

        /** @var array<Page> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Get all pages that do not have tags
     *
     * @param int $offset
     * @param int $limit
     * @return Paginator<Page>
     */
    public function getPagesWithoutTags(int $offset = 0, int $limit = 0): Paginator
    {
        return $this->getAll(
            offset: $offset,
            limit: $limit,
            where: [
                'q.tags IS EMPTY'
            ],
            order: [
                ['q.postDate', 'DESC']
            ]
        );
    }

    /**
     * Get all pages that do not have tags
     *
     * @return int
     */
    public function getPagesWithoutTagsCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p)')
            ->leftJoin('p.tags', 'Page_tags')
            ->where('Page_tags.id IS NULL');
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages that do not have categories
     *
     * @param int $offset
     * @param int $limit
     * @return Paginator<Page>
     */
    public function getPagesWithoutCategories(int $offset = 0, int $limit = 0): Paginator
    {
        return $this->getAll(
            offset: $offset,
            limit: $limit,
            where: [
                'q.categories IS EMPTY'
            ],
            order: [
                ['q.postDate', 'DESC']
            ]
        );
    }

    /**
     * Get all pages that do not have categories
     *
     * @return int
     */
    public function getPagesWithoutCategoriesCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p)')
            ->leftJoin('p.categories', 'Page_categories')
            ->where('Page_categories.id IS NULL');
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages that do not have a feature image
     *
     * @param int $offset
     * @param int $limit
     * @return Paginator<Page>
     */
    public function getPagesWithoutFeatureImage(int $offset = 0, int $limit = 0): Paginator
    {
        return $this->getAll(
            offset: $offset,
            limit: $limit,
            where: [
                'q.featureImage IS NULL'
            ],
            order: [
                ['q.postDate', 'DESC']
            ]
        );
    }

    /**
     * Get all pages that do not have a feature image
     *
     * @return int
     */
    public function getPagesWithoutFeatureImageCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p)')
            ->where('p.featureImage IS NULL');
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages that do not have a sharing message
     *
     * @param int $offset
     * @param int $limit
     * @return Paginator<Page>
     */
    public function getPagesWithoutSharingMessage(int $offset = 0, int $limit = 0): Paginator
    {
        return $this->getAll(
            offset: $offset,
            limit: $limit,
            where: [
                'q.sharingMessage IS NULL'
            ],
            order: [
                ['q.postDate', 'DESC']
            ]
        );
    }

    /**
     * Get all pages that do not have a sharing message
     *
     * @return int
     */
    public function getPagesWithoutSharingMessageCount(): int
    {
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p)')
            ->where('p.sharingMessage IS NULL');
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }
}
