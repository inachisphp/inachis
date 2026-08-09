<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Category;
use Inachis\Repository\Content\CategoryRepository;
use PHPUnit\Framework\TestCase;

final class CategoryRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private EntityRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = $this->createStub(ManagerRegistry::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(CategoryRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([
                'getEntityManager',
                'createQueryBuilder',
                'getAll',
            ])
            ->getMock();

        $this->repository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
    }

    public function testRemoveCallsEntityManagerMethods(): void
    {
        $category = new Category();

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($category);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->remove($category);
    }

    public function testGetRootCategoriesBuildsCorrectQuery(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('getRepository');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with('q.parent is null')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn(['mock_result']);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('q')
            ->willReturn($queryBuilder);

        self::assertSame(
            ['mock_result'],
            $this->repository->getRootCategories(),
        );
    }

    public function testFindByTitleLikeDelegatesToGetAll(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                25,
                0,
                [
                    'q.title LIKE :title',
                    [
                        'title' => '%test%',
                    ],
                ],
                'q.title',
            )
            ->willReturn($paginator);

        self::assertSame(
            $paginator,
            $this->repository->findByTitleLike('test'),
        );
    }

    public function testCountVisibleCategories(): void
    {
        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('COUNT(c.id)')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(12);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($queryBuilder);

        self::assertSame(
            12,
            $this->repository->countVisibleCategories(),
        );
    }

    public function testCountVisibleCategoriesCastsResultToInt(): void
    {
        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('COUNT(c.id)')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('12');

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($queryBuilder);

        self::assertSame(
            12,
            $this->repository->countVisibleCategories(),
        );
    }

    public function testFindBatchBuildsCorrectQuery(): void
    {
        $category = new Category();

        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('c.title', 'ASC')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(10)
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('setFirstResult')
            ->with(20)
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$category]);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($queryBuilder);

        self::assertSame(
            [$category],
            $this->repository->findBatch(10, 20),
        );
    }

    public function testFindBatchReturnsEmptyArrayWhenNoCategoriesExist(): void
    {
        $query = $this->createMock(Query::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->expects($this->once())
            ->method('orderBy')
            ->with('c.title', 'ASC')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(25)
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $this->repository
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->with('c')
            ->willReturn($queryBuilder);

        self::assertSame(
            [],
            $this->repository->findBatch(25, 0),
        );
    }
}
