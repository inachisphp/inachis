<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\QueryBuilder;
use Inachis\Entity\Content\{Category, Page, Tag};
use Inachis\Entity\Media\Image;
use Inachis\Repository\AbstractRepository;
use Inachis\Repository\Content\PageRepositoryInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Enum\EditorialStatus;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

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
    public function __construct(
        private TagAwareCacheInterface $cache,
        ManagerRegistry $registry
    ) {
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
     *   toDate?:\DateTimeImmutable,
     *   expired?:string
     * } $filters
     * @param string $type
     * @param int $limit
     * @param int $offset
     * @param string $sort
     * @return Paginator<Page>
     */
    public function getFilteredOfTypeByPostDate(
        array $filters,
        string $type,
        int $limit,
        int $offset,
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
                    fn($t) => $t,
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
            $where[1]['excludeIds'] = [
                'value' => $filters['excludeIds'],
            ];
        }
        if (!empty($filters['fromDate'])) {
            $where[0] .= ' AND q.postDate >= :fromDate';
            $where[1]['fromDate'] = $filters['fromDate'];
        }
        if (!empty($filters['toDate'])) {
            $where[0] .= ' AND q.postDate <= :toDate';
            $where[1]['toDate'] = $filters['toDate'];
        }
        if (!empty($filters['expired'])) {
            $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            if ($filters['expired'] === 'expired') {
                $where[0] .= ' AND q.expireDate IS NOT NULL AND q.expireDate < :now';
            } else {
                $where[0] .= ' AND (q.expireDate IS NULL OR q.expireDate >= :now)';
            }
            $where[1]['now'] = $now;
        }

        return $this->getAll(
            $limit,
            $offset,
            $where,
            $this->determineOrderBy($sort),
            [],
            $join
        );
    }

    /**
     * Applies filters to the QueryBuilder to specify that only content
     * which is Published, visible, and within the publishing window
     * should be returned.
     *
     * @param QueryBuilder $qb
     * @return QueryBuilder
     */
    private function applyLiveFilter(QueryBuilder $qb): QueryBuilder
    {
        $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        return $qb
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate >= :now)')
            ->andWhere('p.visible = :visible')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->setParameter('visible', true);
    }

    /**
     * Get all pages with the given category
     *
     * @param Category $category
     * @param int $limit
     * @param int $offset
     * @return array<Page>
     */
    public function getLiveContentWithCategory(Category $category, int $limit = 0, int $offset = 0)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId(), 'uuid_binary')
            ->orderBy('p.postDate', 'DESC');
        $qb = $this->applyLiveFilter($qb);
        if ($limit > 0) {
            $qb = $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
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
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->leftJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
        ;
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages with the given tag
     *
     * @param Tag $tag
     * @param int $limit
     * @param int $offset
     * @return array<Page>
     */
    public function getLiveContentWithTag(Tag $tag, int $limit = 0, int $offset = 0): array
    {
        $now = new \DateTimeImmutable();

        $qb = $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.tags', 't')
            ->where('t.id = :tagId')
            ->setParameter('tagId', $tag->getId(), 'uuid_binary')
            ->orderBy('p.postDate', 'DESC');
        $qb = $this->applyLiveFilter($qb);
        if ($limit > 0) {
            $qb = $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        /** @var array<Page> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Get all pages with the given ids
     *
     * @param list<string> $ids
     * @return list<Page>
     */
    public function getFilteredIds(array $ids): array
    {
        /** @var list<Page> */
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all pages that use the given image
     *
     * @param Image $image
     * @return list<Page>
     */
    public function getPostsUsingImage(Image $image): array
    {
        /** @var list<Page> */
        return $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.content LIKE :filename OR p.featureImage = :image')
            ->setParameter('filename', '%' . $image->getFilename() . '%')
            ->setParameter('image', $image)
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get the top N pages with the largest image size calculated
     *
     * @param int $limit
     * @return list<Page>
     */
    public function getTopPagesByImageSize(int $limit = 10): array
    {
        /** @var list<Page> */
        return $this->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.imageSize', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all pages that do not have tags
     *
     * @param int $limit
     * @param int $offset
     * @return Paginator<Page>
     */
    public function getPagesWithoutTags(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
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
        return $this->cache->get('pages_without_tags_count', function (ItemInterface $item) {
            $item->expiresAfter(7200);
            $item->tag(['page_metrics']);

            $qb = $this->createQueryBuilder('p');
            $qb = $qb
                ->select('COUNT(p)')
                ->leftJoin('p.tags', 'Page_tags')
                ->where('Page_tags.id IS NULL');
            /** @var int */
            return $qb->getQuery()->getSingleScalarResult();
        });
    }

    /**
     * Get all pages that do not have categories
     *
     * @param int $limit
     * @param int $offset
     * @return Paginator<Page>
     */
    public function getPagesWithoutCategories(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
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
        return $this->cache->get(
            'pages_without_categories_count',
            function (ItemInterface $item)
            {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);
                
                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->leftJoin('p.categories', 'Page_categories')
                    ->where('Page_categories.id IS NULL');
                /** @var int */
                return $qb->getQuery()->getSingleScalarResult();
            }
        );
    }

    /**
     * Get all pages that do not have a feature image
     *
     * @param int $limit
     * @param int $offset
     * @return Paginator<Page>
     */
    public function getPagesWithoutFeatureImage(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
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
        return $this->cache->get(
            'pages_without_feature_image_count',
            function (ItemInterface $item)
            {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);

                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->where('p.featureImage IS NULL');
                /** @var int */
                return $qb->getQuery()->getSingleScalarResult();
            }
        );
    }

    /**
     * Get all pages that do not have a sharing message
     *
     * @param int $limit
     * @param int $offset
     * @return Paginator<Page>
     */
    public function getPagesWithoutSharingMessage(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
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
        return $this->cache->get(
            'pages_without_sharing_message_count',
            function (ItemInterface $item)
            {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);

                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->where('p.sharingMessage IS NULL');
                /** @var int */
                return $qb->getQuery()->getSingleScalarResult();
            }
        );
    }

    /**
     * Returns an array of Posts/Pages that are recent drafts
     * ordered by their intended publication date
     *
     * @param int $limit
     * @return array<Page>
     */
    public function findRecentDrafts(int $limit = 5): array
    {
        /** @var array<Page> */
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', EditorialStatus::DRAFT)
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns an array of recently published Posts/Pages
     *
     * @param int $limit
     * @return array<Page>
     */
    public function findRecentPublished(int $limit = 5): array
    {
        /** @var array<Page> */
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now',  new \DateTimeImmutable('now'))
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns an array of {@link Page} that are published but not yet
     * at their scheduled publication date
     *
     * @param int $limit
     * @return array<Page>
     */
    public function findUpcoming(int $limit = 5): array
    {
        /** @var array<Page> */
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.postDate > :now')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now',  new \DateTimeImmutable('now'))
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the most recently edited {@link Page}
     *
     * @return Page|null
     */
    public function findMostRecentlyEditedDraft(): ?Page
    {
        return $this->findOneBy(
            ['status' => EditorialStatus::DRAFT],
            ['updatedAt' => 'DESC']
        );
    }

    /**
     * Get the counts shown on the dashboard.
     *
     * @return array{
     *     drafts:int,
     *     published:int,
     *     upcoming:int
     * }
     */
    public function getDashboardCounts(): array
    {
        $now = new \DateTimeImmutable();

        /** @var array{
         *     drafts:string|int|null,
         *     published:string|int|null,
         *     upcoming:string|int|null
         * } $result
         */
        $result = $this->getEntityManager()
            ->getConnection()
            ->fetchAssociative(
                '
                SELECT
                    SUM(
                        CASE
                            WHEN status = :draft
                            THEN 1
                            ELSE 0
                        END
                    ) AS drafts,

                    SUM(
                        CASE
                            WHEN status = :published
                            AND post_date <= :now
                            THEN 1
                            ELSE 0
                        END
                    ) AS published,

                    SUM(
                        CASE
                            WHEN status = :published
                            AND post_date > :now
                            THEN 1
                            ELSE 0
                        END
                    ) AS upcoming

                FROM page
                WHERE type = :type
                ',
                [
                    'draft' => EditorialStatus::DRAFT->value,
                    'published' => EditorialStatus::PUBLISHED->value,
                    'now' => $now->format('Y-m-d H:i:s'),
                    'type' => Page::TYPE_POST,
                ]
            );

        return [
            'drafts' => (int) ($result['drafts'] ?? 0),
            'published' => (int) ($result['published'] ?? 0),
            'upcoming' => (int) ($result['upcoming'] ?? 0),
        ];
    }
}
