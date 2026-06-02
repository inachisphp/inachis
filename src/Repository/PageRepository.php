<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\{Category, Image, Page, Tag, Url};
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for retrieving {@link Page} entities
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
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('p')
            ->leftJoin('p.categories', 'Page_categories')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('Page_categories.id', ':categoryId'),
                    $qb->expr()->eq('p.status', '\'published\''),
                    $qb->expr()->eq('p.visibility', '1'),
                    $qb->expr()->eq('p.type', '\'post\'')
                )
            )
            ->orderBy('p.postDate', 'DESC')
            ->setParameter('categoryId', $category->getId());
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
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p) AS numPages')
            ->leftJoin('p.categories', 'Page_categories')
            ->where('Page_categories.id = :categoryId')
            ->setParameter('categoryId', $category);
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
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('p')
            ->leftJoin('p.tags', 'Page_tags')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('Page_tags.id', ':tagId'),
                    'p.status=\'published\'',
                    'p.visibility=\'1\'',
                    'p.type=\'post\''
                )
            )
            ->orderBy('p.postDate', 'DESC')
            ->setParameter('tagId', $tag->getId());
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
        $qb = $this->createQueryBuilder('p');
        $qb = $qb
            ->select('COUNT(p) AS numPages')
            ->leftJoin('p.tags', 'Page_tags')
            ->where('Page_tags.id = :tagId AND p.status = \'published\' AND p.visibility = \'1\' AND p.type = \'post\'')
            ->setParameter('tagId', $tag);
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
     * @return array<array<string>>
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
            'modDate asc' => [['q.modDate', 'ASC']],
            'modDate desc' => [['q.modDate', 'DESC']],
            'postDate asc' => [['q.postDate', 'ASC']],
            default => [['q.postDate', 'DESC']],
        };
    }

    /**
     * Get all content of a certain type, ordered by post date
     *
     * @param array<string, mixed> $filters
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
            $filters,
        ];
        if ($type != '*') {
            $where = [
                'q.type = :type',
                array_merge(
                    [
                        'type' => $type,
                    ],
                    $filters
                )
            ];
        }
        /** @var array<string, string> $filters['categories'] */
        if (!empty($filters['categories'])) {
            $where[0] .= ' AND c.id IN (:categories)';
            $where[1]['categories'] = array_is_list($filters['categories']) ? $filters['categories'] : array_keys($filters['categories']);
            $join[] = ['leftJoin', 'q.categories', 'c'];
        }
        /** @var array<string, string> $filters['tags'] */
        if (!empty($filters['tags'])) {
            $where[0] .= ' AND t.id IN (:tags)';
            $where[1]['tags'] = array_is_list($filters['tags']) ? $filters['tags'] : array_keys($filters['tags']);
            $join[] = ['leftJoin', 'q.tags', 't'];
        }
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
        if (!empty($filters['excludeIds'])) {
            $where[0] .= ' AND q.id NOT IN (:excludeIds)';
        }
        if (!empty($filters['fromDate'])) {
            $where[0] .= ' AND q.postDate >= :fromDate';
        }
        if (!empty($filters['toDate'])) {
            $where[0] .= ' AND q.postDate <= :toDate';
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
     * @param array<string> $ids
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
                    'filename' => '%' . $image->getFilename() . '%',
                    'image' => $image->getId(),
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
