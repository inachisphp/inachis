<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Tag;
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\AbstractRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Repository for retrieving {@link Page} entities.
 *
 * @extends AbstractRepository<Page>
 */
class PageRepository extends AbstractRepository implements PageRepositoryInterface
{
    /**
     * The maximum number of items to show in the admin interface.
     */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 10;

    /**
     * PageRepository constructor.
     */
    public function __construct(
        private TagAwareCacheInterface $cache,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Page::class);
    }

    /**
     * Remove the given page from the database.
     *
     * @param Page $page the {@link Page} entity to be removed
     */
    public function remove(Page $page): void
    {
        $this->getEntityManager()->remove($page);
    }

    /**
     * Determine the order by clause for the query builder.
     *
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
     * Get all content of a certain type, ordered by post date.
     *
     * @param array{
     *   categories?:array<string>,
     *   tags?:array<string>,
     *   status?:string,
     *   visible?:bool,
     *   visibility?:bool,
     *   issues?:string,
     *   keyword?:string,
     *   excludeIds?:list<string>,
     *   fromDate?:\DateTimeImmutable,
     *   toDate?:\DateTimeImmutable,
     *   expired?:string
     * } $filters
     *
     * @return Paginator<Page>
     */
    public function getFilteredOfTypeByPostDate(
        array $filters,
        string $type,
        int $limit,
        int $offset,
        string $sort = 'postDate desc',
    ): Paginator {
        $allowedIssueFilters = ['categories', 'image', 'snippet', 'tags'];
        if (
            isset($filters['issues'])
            && in_array($filters['issues'], $allowedIssueFilters, true)
        ) {
            return match ($filters['issues']) {
                'categories' => $this->getPagesWithoutCategories($limit, $offset),
                'image' => $this->getPagesWithoutFeatureImage($limit, $offset),
                'snippet' => $this->getPagesWithoutFeatureSnippet($limit, $offset),
                default => $this->getPagesWithoutTags($limit, $offset),
            };
        }
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
        if ('*' != $type) {
            $where = [
                'q.type = :type',
                array_merge(
                    [
                        'type' => $type,
                    ],
                    [],
                ),
            ];
        }
        if (!empty($filters['categories'])) {
            $where[0] .= ' AND c.id IN (:categories)';
            $where[1]['categories'] = [
                'value' => implode(',', array_map(
                    static fn (string $t): string => $t,
                    array_is_list($filters['categories']) ? $filters['categories'] : array_keys($filters['categories']),
                )),
                'type' => 'uuid_binary',
            ];
            $join[] = ['leftJoin', 'q.categories', 'c'];
        }
        if (!empty($filters['tags'])) {
            $where[0] .= ' AND t.id IN (:tags)';
            $where[1]['tags'] = [
                'value' => implode(',', array_map(
                    static fn (string $t): string => $t,
                    array_is_list($filters['tags']) ? $filters['tags'] : array_keys($filters['tags']),
                )),
                'type' => 'uuid_binary',
            ];
            $join[] = ['leftJoin', 'q.tags', 't'];
        }
        if (!empty($filters['status'])) {
            if ('expired' === $filters['status']) {
                $now = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
                $where[0] .= ' AND q.expireDate IS NOT NULL AND q.expireDate < :now';
                $where[1]['now'] = $now;
            } else {
                $where[0] .= ' AND q.status = :status';
                $where[1]['status'] = $filters['status'];
            }
        }
        if (isset($filters['visibility'])) {
            $where[0] .= ' AND q.visible = :visible';
            $where[1]['visible'] = $filters['visibility'];
        }
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.content LIKE :keyword )';
            $where[1]['keyword'] = '%'.$filters['keyword'].'%';
        }
        if (!empty($filters['excludeIds'])) {
            $binaryIds = array_map(
                static fn (string $id): string => Uuid::fromString($id)->getBytes(),
                $filters['excludeIds'],
            );
            $where[0] .= ' AND q.id NOT IN (:excludeIds)';
            $where[1]['excludeIds'] = [
                'value' => $binaryIds,
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

        return $this->getAll(
            $limit,
            $offset,
            $where,
            $this->determineOrderBy($sort),
            [],
            $join,
        );
    }

    /**
     * Applies filters to the QueryBuilder to specify that only content
     * which is Published, visible, and within the publishing window
     * should be returned.
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
     * Get all pages with the given category.
     *
     * @return array<Page>
     */
    public function getLiveContentWithCategory(Category $category, int $limit = 0, int $offset = 0): array
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

        /** @var array<Page> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get the number of pages with the given category.
     */
    public function getPagesWithCategoryCount(Category $category): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->leftJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all pages with the given tag.
     *
     * @return array<Page>
     */
    public function getLiveContentWithTag(Tag $tag, int $limit = 0, int $offset = 0): array
    {
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

        /** @var array<Page> $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get all pages with the given ids.
     *
     * @param list<string> $ids
     *
     * @return list<Page>
     */
    public function getFilteredIds(array $ids): array
    {
        $binaryIds = array_map(
            static fn (string $id): string => Uuid::fromString($id)->getBytes(),
            $ids,
        );

        /** @var list<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $binaryIds)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Get all pages that use the given image.
     *
     * @return list<Page>
     */
    public function getPostsUsingImage(Image $image): array
    {
        /** @var list<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p')
            ->where('p.content LIKE :filename OR p.featureImage = :image')
            ->setParameter('filename', '%'.$image->getFilename().'%')
            ->setParameter('image', $image)
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Get the top N pages with the largest image size calculated.
     *
     * @return list<Page>
     */
    public function getTopPagesByImageSize(int $limit = 10): array
    {
        /** @var list<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->select('p')
            ->orderBy('p.imageSize', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Get all pages that do not have tags.
     *
     * @return Paginator<Page>
     */
    public function getPagesWithoutTags(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
            where: [
                'q.tags IS EMPTY',
            ],
            order: [
                ['q.postDate', 'DESC'],
            ],
        );
    }

    /**
     * Get all pages that do not have tags.
     */
    public function getPagesWithoutTagsCount(): int
    {
        return (int) $this->cache->get('pages_without_tags_count', function (ItemInterface $item) {
            $item->expiresAfter(7200);
            $item->tag(['page_metrics']);

            $qb = $this->createQueryBuilder('p');
            $qb = $qb
                ->select('COUNT(p)')
                ->leftJoin('p.tags', 'Page_tags')
                ->where('Page_tags.id IS NULL');

            return (int) $qb->getQuery()->getSingleScalarResult();
        });
    }

    /**
     * Get all pages that do not have categories.
     *
     * @return Paginator<Page>
     */
    public function getPagesWithoutCategories(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
            where: [
                'q.categories IS EMPTY',
            ],
            order: [
                ['q.postDate', 'DESC'],
            ],
        );
    }

    /**
     * Get all pages that do not have categories.
     */
    public function getPagesWithoutCategoriesCount(): int
    {
        return (int) $this->cache->get(
            'pages_without_categories_count',
            function (ItemInterface $item) {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);

                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->leftJoin('p.categories', 'Page_categories')
                    ->where('Page_categories.id IS NULL');

                return (int) $qb->getQuery()->getSingleScalarResult();
            },
        );
    }

    /**
     * Get all pages that do not have a feature image.
     *
     * @return Paginator<Page>
     */
    public function getPagesWithoutFeatureImage(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
            where: [
                'q.featureImage IS NULL',
            ],
            order: [
                ['q.postDate', 'DESC'],
            ],
        );
    }

    /**
     * Get all pages that do not have a feature image.
     */
    public function getPagesWithoutFeatureImageCount(): int
    {
        return (int) $this->cache->get(
            'pages_without_feature_image_count',
            function (ItemInterface $item) {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);

                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->where('p.featureImage IS NULL');

                return (int) $qb->getQuery()->getSingleScalarResult();
            },
        );
    }

    /**
     * Get all pages that do not have a sharing message.
     *
     * @return Paginator<Page>
     */
    public function getPagesWithoutFeatureSnippet(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
            where: [
                'q.featureSnippet IS NULL',
            ],
            order: [
                ['q.postDate', 'DESC'],
            ],
        );
    }

    /**
     * Get all pages that do not have a sharing message.
     */
    public function getPagesWithoutFeatureSnippetCount(): int
    {
        return (int) $this->cache->get(
            'pages_without_sharing_message_count',
            function (ItemInterface $item) {
                $item->expiresAfter(7200);
                $item->tag(['page_metrics']);

                $qb = $this->createQueryBuilder('p');
                $qb = $qb
                    ->select('COUNT(p)')
                    ->where('p.featureSnippet IS NULL');

                return (int) $qb->getQuery()->getSingleScalarResult();
            },
        );
    }

    /**
     * Returns an array of Posts/Pages that are recent drafts
     * ordered by their intended publication date.
     *
     * @return array<Page>
     */
    public function findRecentDrafts(int $limit = 5): array
    {
        /** @var array<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', EditorialStatus::DRAFT)
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Returns an array of recently published Posts/Pages.
     *
     * @return array<Page>
     */
    public function findRecentPublished(int $limit = 5): array
    {
        /** @var array<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable('now'))
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Returns an array of {@link Page} that are published but not yet
     * at their scheduled publication date.
     *
     * @return array<Page>
     */
    public function findUpcoming(int $limit = 5): array
    {
        /** @var array<Page> $result */
        $result = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.postDate > :now')
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable('now'))
            ->orderBy('p.postDate', 'ASC')
            ->addOrderBy('p.updatedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * Returns the most recently edited {@link Page}.
     */
    public function findMostRecentlyEditedDraft(): ?Page
    {
        return $this->findOneBy(
            ['status' => EditorialStatus::DRAFT],
            ['updatedAt' => 'DESC'],
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
         *     drafts?:string|int|null,
         *     published?:string|int|null,
         *     upcoming?:string|int|null
         * }|false $result
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
                ],
            );

        return [
            'drafts' => is_array($result) ? (int) ($result['drafts'] ?? 0) : 0,
            'published' => is_array($result) ? (int) ($result['published'] ?? 0) : 0,
            'upcoming' => is_array($result) ? (int) ($result['upcoming'] ?? 0) : 0,
        ];
    }
}
