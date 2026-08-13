<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Content;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Tag;
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\Content\PageRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class PageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private TagAwareCacheInterface $cache;

    private PageRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(TagAwareCacheInterface::class);

        $this->repository = $this->getMockBuilder(PageRepository::class)
            ->setConstructorArgs([
                $this->cache,
                $registry,
            ])
            ->onlyMethods([
                'getEntityManager',
                'createQueryBuilder',
                'getAll',
                'findOneBy',
            ])
            ->getMock();

        $this->repository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testGetMaxItemsToShow(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('getRepository');

        self::assertSame(
            PageRepository::MAX_ITEMS_TO_SHOW_ADMIN,
            $this->repository->getMaxItemsToShow(),
        );
    }

    public function testRemove(): void
    {
        $page = new Page();

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($page);

        $this->repository->remove($page);
    }

    public function testDetermineOrderBy(): void
    {
        $cases = [
            'title asc' => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'updatedAt asc' => [
                ['q.updatedAt', 'ASC'],
            ],
            'updatedAt desc' => [
                ['q.updatedAt', 'DESC'],
            ],
            'postDate asc' => [
                ['q.postDate', 'ASC'],
            ],
            'anything else' => [
                ['q.postDate', 'DESC'],
            ],
        ];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');

        foreach ($cases as $input => $expected) {
            self::assertSame(
                $expected,
                $method->invoke($this->repository, $input),
            );
        }
    }

    public function testGetFilteredOfTypeByPostDateUsesTypeAndFilters(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                [
                    'q.type = :type AND q.status = :status AND q.visible = :visible AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.content LIKE :keyword )',
                    [
                        'type' => 'post',
                        'status' => EditorialStatus::PUBLISHED->value,
                        'visible' => true,
                        'keyword' => '%test%',
                    ],
                ],
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                [
                    'status' => EditorialStatus::PUBLISHED->value,
                    'visibility' => true,
                    'keyword' => 'test',
                ],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateHandlesWildcardType(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                [
                    '1=1',
                    [],
                ],
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                [],
                '*',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateRemovesEmptyCategoryAndTagFilters(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                [
                    'q.type = :type',
                    [
                        'type' => 'post',
                    ],
                ],
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                [
                    'categories' => [],
                    'tags' => [],
                ],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateHandlesCategoriesAndTags(): void
    {
        $category = (new Category())->setId(Uuid::uuid1());
        $tag = (new Tag('test'))->setId(Uuid::uuid1());

        $categoryId = $category->getId();
        $tagId = $tag->getId();

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                self::callback(
                    static function (array $where) use (
                        $categoryId,
                        $tagId,
                    ): bool {
                        return str_contains(
                            $where[0],
                            'c.id IN (:categories)',
                        )
                            && str_contains(
                                $where[0],
                                't.id IN (:tags)',
                            )
                            && $where[1]['categories']['value']
                                === $categoryId->toString()
                            && 'uuid_binary'
                                === $where[1]['categories']['type']
                            && $where[1]['tags']['value']
                                === $tagId->toString()
                            && 'uuid_binary'
                                === $where[1]['tags']['type'];
                    },
                ),
                [['q.postDate', 'DESC']],
                [],
                [
                    ['leftJoin', 'q.categories', 'c'],
                    ['leftJoin', 'q.tags', 't'],
                ],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                [
                    'categories' => [$categoryId],
                    'tags' => [$tagId],
                ],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateHandlesExpiredStatus(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                self::callback(
                    static function (array $where): bool {
                        return str_contains(
                            $where[0],
                            'q.expireDate IS NOT NULL AND q.expireDate < :now',
                        )
                            && isset($where[1]['now'])
                            && is_string($where[1]['now']);
                    },
                ),
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                ['status' => 'expired'],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateHandlesFromAndToDates(): void
    {
        $from = new \DateTimeImmutable('2026-01-01 00:00:00');
        $to = new \DateTimeImmutable('2026-12-31 23:59:59');

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                self::callback(
                    static function (array $where) use ($from, $to): bool {
                        return str_contains(
                            $where[0],
                            'q.postDate >= :fromDate',
                        )
                            && str_contains(
                                $where[0],
                                'q.postDate <= :toDate',
                            )
                            && $where[1]['fromDate'] === $from
                            && $where[1]['toDate'] === $to;
                    },
                ),
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                [
                    'fromDate' => $from,
                    'toDate' => $to,
                ],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredOfTypeByPostDateAcceptsUuidObjectsForExcludeIds(): void
    {
        $uuid = Uuid::uuid4();

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                self::callback(
                    static function (array $where) use ($uuid): bool {
                        return $where[1]['excludeIds']['value'] === [
                            $uuid->getBytes(),
                        ];
                    },
                ),
                [['q.postDate', 'DESC']],
                [],
                [],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate(
                ['excludeIds' => [$uuid]],
                'post',
                10,
                5,
            ),
        );
    }

    public function testGetFilteredIdsConvertsStringIdsToBinary(): void
    {
        $uuid = Uuid::uuid4();
        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('p.id IN (:ids)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('ids', [$uuid->getBytes()])
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [],
            $this->repository->getFilteredIds([$uuid->toString()]),
        );
    }

    public function testGetFilteredIdsAcceptsUuidObjects(): void
    {
        $uuid = Uuid::uuid4();
        $page = new Page();

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('p.id IN (:ids)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('ids', [$uuid->getBytes()])
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->getFilteredIds([$uuid]),
        );
    }

    public function testGetLiveContentWithCategory(): void
    {
        $category = new Category();
        $page = new Page('test page');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.categories', 'c')
            ->willReturnSelf();

        $qb->expects($this->exactly(5))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->getLiveContentWithCategory(
                $category,
                10,
                5,
            ),
        );
    }

    public function testGetLiveContentWithCategoryWithoutPagination(): void
    {
        $category = new Category();

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.categories', 'c')
            ->willReturnSelf();

        $qb->expects($this->exactly(5))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->never())
            ->method('setMaxResults');

        $qb->expects($this->never())
            ->method('setFirstResult');

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [],
            $this->repository->getLiveContentWithCategory($category),
        );
    }

    public function testGetPagesWithCategoryCount(): void
    {
        $category = new Category();

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(2);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.categories', 'c')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('c.id = :categoryId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('categoryId', $category->getId())
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            2,
            $this->repository->getPagesWithCategoryCount($category),
        );
    }

    public function testGetLiveContentWithTag(): void
    {
        $tag = new Tag('test');
        $page = new Page('test page');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.tags', 't')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('t.id = :tagId')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->getLiveContentWithTag($tag, 10, 5),
        );
    }

    public function testGetLiveContentWithTagWithoutPagination(): void
    {
        $tag = new Tag('test');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.tags', 't')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('t.id = :tagId')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('andWhere')
            ->willReturnSelf();

        $qb->expects($this->exactly(4))
            ->method('setParameter')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->never())
            ->method('setMaxResults');

        $qb->expects($this->never())
            ->method('setFirstResult');

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [],
            $this->repository->getLiveContentWithTag($tag),
        );
    }

    public function testGetPostsUsingImage(): void
    {
        $image = new Image();
        $image->setId(Uuid::uuid4());
        $image->setFilename('test.jpeg');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with(
                'p.content LIKE :filename OR p.featureImage = :image',
            )
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with(
                self::callback(
                    static fn (string $name): bool => in_array($name, ['filename', 'image'], true),
                ),
                self::anything(),
            )
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(25)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [],
            $this->repository->getPostsUsingImage($image),
        );
    }

    public function testGetTopPagesByImageSize(): void
    {
        $page = new Page('test page');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('select')
            ->with('p')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.imageSize', 'DESC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->getTopPagesByImageSize(5),
        );
    }

    public function testGetPagesWithoutTags(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                limit: 10,
                offset: 5,
                where: [
                    'q.tags IS EMPTY',
                ],
                order: [
                    ['q.postDate', 'DESC'],
                ],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getPagesWithoutTags(10, 5),
        );
    }

    public function testGetPagesWithoutCategories(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                limit: 10,
                offset: 5,
                where: [
                    'q.categories IS EMPTY',
                ],
                order: [
                    ['q.postDate', 'DESC'],
                ],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getPagesWithoutCategories(10, 5),
        );
    }

    public function testGetPagesWithoutFeatureImage(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                limit: 10,
                offset: 5,
                where: [
                    'q.featureImage IS NULL',
                ],
                order: [
                    ['q.postDate', 'DESC'],
                ],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getPagesWithoutFeatureImage(10, 5),
        );
    }

    public function testGetPagesWithoutFeatureSnippet(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                limit: 10,
                offset: 5,
                where: [
                    'q.featureSnippet IS NULL',
                ],
                order: [
                    ['q.postDate', 'DESC'],
                ],
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->getPagesWithoutFeatureSnippet(10, 5),
        );
    }

    public function testGetPagesWithoutTagsCountUsesCache(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $query = $this->createMock(Query::class);
        $qb = $this->createMock(QueryBuilder::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(7200);

        $item->expects($this->once())
            ->method('tag')
            ->with(['page_metrics']);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(7);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.tags', 'Page_tags')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('Page_tags.id IS NULL')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'pages_without_tags_count',
                self::callback('is_callable'),
            )
            ->willReturnCallback(
                static function (
                    string $key,
                    callable $callback,
                ) use ($item): int {
                    return $callback($item);
                },
            );

        self::assertSame(
            7,
            $this->repository->getPagesWithoutTagsCount(),
        );
    }

    public function testGetPagesWithoutCategoriesCountUsesCache(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $query = $this->createMock(Query::class);
        $qb = $this->createMock(QueryBuilder::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(7200);

        $item->expects($this->once())
            ->method('tag')
            ->with(['page_metrics']);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(8);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('leftJoin')
            ->with('p.categories', 'Page_categories')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('Page_categories.id IS NULL')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'pages_without_categories_count',
                self::callback('is_callable'),
            )
            ->willReturnCallback(
                static function (
                    string $key,
                    callable $callback,
                ) use ($item): int {
                    return $callback($item);
                },
            );

        self::assertSame(
            8,
            $this->repository->getPagesWithoutCategoriesCount(),
        );
    }

    public function testGetPagesWithoutFeatureImageCountUsesCache(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $query = $this->createMock(Query::class);
        $qb = $this->createMock(QueryBuilder::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(7200);

        $item->expects($this->once())
            ->method('tag')
            ->with(['page_metrics']);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(9);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('p.featureImage IS NULL')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'pages_without_feature_image_count',
                self::callback('is_callable'),
            )
            ->willReturnCallback(
                static function (
                    string $key,
                    callable $callback,
                ) use ($item): int {
                    return $callback($item);
                },
            );

        self::assertSame(
            9,
            $this->repository->getPagesWithoutFeatureImageCount(),
        );
    }

    public function testGetPagesWithoutFeatureSnippetCountUsesCache(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $query = $this->createMock(Query::class);
        $qb = $this->createMock(QueryBuilder::class);

        $item->expects($this->once())
            ->method('expiresAfter')
            ->with(7200);

        $item->expects($this->once())
            ->method('tag')
            ->with(['page_metrics']);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(10);

        $qb->expects($this->once())
            ->method('select')
            ->with('COUNT(p)')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('where')
            ->with('p.featureSnippet IS NULL')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->with(
                'pages_without_sharing_message_count',
                self::callback('is_callable'),
            )
            ->willReturnCallback(
                static function (
                    string $key,
                    callable $callback,
                ) use ($item): int {
                    return $callback($item);
                },
            );

        self::assertSame(
            10,
            $this->repository->getPagesWithoutFeatureSnippetCount(),
        );
    }

    public function testFindRecentDrafts(): void
    {
        $page = new Page('draft');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setParameter')
            ->with('status', EditorialStatus::DRAFT)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('p.updatedAt', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->findRecentDrafts(),
        );
    }

    public function testFindRecentPublished(): void
    {
        $page = new Page('published');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.postDate <= :now')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with(
                self::callback(
                    static fn (string $name): bool => in_array($name, ['status', 'now'], true),
                ),
                self::anything(),
            )
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('p.updatedAt', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->findRecentPublished(),
        );
    }

    public function testFindUpcoming(): void
    {
        $page = new Page('upcoming');

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$page]);

        $qb = $this->createMock(QueryBuilder::class);

        $qb->expects($this->once())
            ->method('where')
            ->with('p.status = :status')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('andWhere')
            ->with('p.postDate > :now')
            ->willReturnSelf();

        $qb->expects($this->exactly(2))
            ->method('setParameter')
            ->with(
                self::callback(
                    static fn (string $name): bool => in_array($name, ['status', 'now'], true),
                ),
                self::anything(),
            )
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('orderBy')
            ->with('p.postDate', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('addOrderBy')
            ->with('p.updatedAt', 'ASC')
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(5)
            ->willReturnSelf();

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('p')
            ->willReturn($qb);

        self::assertSame(
            [$page],
            $this->repository->findUpcoming(),
        );
    }

    public function testFindMostRecentlyEditedDraft(): void
    {
        $page = new Page('draft');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(
                ['status' => EditorialStatus::DRAFT],
                ['updatedAt' => 'DESC'],
            )
            ->willReturn($page);

        self::assertSame(
            $page,
            $this->repository->findMostRecentlyEditedDraft(),
        );
    }

    public function testFindMostRecentlyEditedDraftReturnsNull(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(
                ['status' => EditorialStatus::DRAFT],
                ['updatedAt' => 'DESC'],
            )
            ->willReturn(null);

        self::assertNull(
            $this->repository->findMostRecentlyEditedDraft(),
        );
    }

    public function testGetDashboardCounts(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with(
                self::callback('is_string'),
                self::callback(
                    static function (array $parameters): bool {
                        return $parameters['draft']
                            === EditorialStatus::DRAFT->value
                            && $parameters['published']
                            === EditorialStatus::PUBLISHED->value
                            && Page::TYPE_POST
                            === $parameters['type']
                            && is_string($parameters['now']);
                    },
                ),
            )
            ->willReturn([
                'drafts' => 3,
                'published' => 7,
                'upcoming' => 2,
            ]);

        $this->entityManager
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertSame(
            [
                'drafts' => 3,
                'published' => 7,
                'upcoming' => 2,
            ],
            $this->repository->getDashboardCounts(),
        );
    }

    public function testGetDashboardCountsHandlesNullResults(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'drafts' => null,
                'published' => null,
                'upcoming' => null,
            ]);

        $this->entityManager
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        self::assertSame(
            [
                'drafts' => 0,
                'published' => 0,
                'upcoming' => 0,
            ],
            $this->repository->getDashboardCounts(),
        );
    }
}
