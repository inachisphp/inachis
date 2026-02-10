<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Inachis\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class SeriesRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private SeriesRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(SeriesRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'createQueryBuilder', 'getAll'])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
    }

    public function testRemoveCallsEntityManager(): void
    {
        $series = new Series();

        $this->repository->expects($this->never())->method('createQueryBuilder');
        $this->entityManager->expects($this->once())->method('remove')->with($series);
        $this->entityManager->expects($this->once())->method('flush');

        $this->repository->remove($series);
    }

    public function testGetSeriesByPostReturnsSeries(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $page = $this->createStub(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $qb = $this->mockQueryBuilder($query);

        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getSeriesByPost($page);
        $this->assertInstanceOf(Series::class, $result);
    }

    public function testGetSeriesByPostReturnsNullWhenNotFound(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $page = $this->createStub(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willReturn(null);

        $qb = $this->mockQueryBuilder($query);
        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getSeriesByPost($page);
        $this->assertNull($result);
    }

    public function testGetPublishedSeriesByPost(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $page = $this->createStub(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $qb = $this->mockQueryBuilder($query);
        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublishedSeriesByPost($page);
        $this->assertInstanceOf(Series::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPublicSeriesByYearAndUrl(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $year = '2025';
        $url = 'my-series';

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $expr = $this->createStub(Expr::class);
        $expr->method('like')->willReturn($this->createStub(Comparison::class));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('select')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('getQuery')->willReturn($query);
        $qb->expects($this->atLeast(1))->method('expr')->willReturn($expr);

        $this->repository->expects($this->atLeast(1))
            ->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublicSeriesByYearAndUrl($year, $url);
        $this->assertInstanceOf(Series::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPublicSeriesByYearAndUrlReturnsNull(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $year = '2025';
        $url = 'non-existent';

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willReturn(null);

        $expr = $this->createStub(Expr::class);
        $expr->method('like')->willReturn($this->createStub(Comparison::class));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('select')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('getQuery')->willReturn($query);
        $qb->expects($this->atLeast(1))->method('expr')->willReturn($expr);

        $this->repository->expects($this->atLeast(1))->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublicSeriesByYearAndUrl($year, $url);
        $this->assertNull($result);
    }

    public function testGetFilteredWithKeyword(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $filters = ['keyword' => 'test'];
        $offset = 0;
        $limit = 10;
        $sort = 'modDate asc';

        $paginator = $this->createStub(Paginator::class);

        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                $offset,
                $limit,
                $this->callback(fn($where) => is_array($where) && strpos($where[0], 'LIKE :keyword') !== false),
                [['q.modDate', 'ASC']],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered($filters, $offset, $limit, $sort);
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $filters = [];
        $offset = 0;
        $limit = 5;
        $sort = 'modDate asc';

        $paginator = $this->createStub(Paginator::class);

        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                $offset,
                $limit,
                ['1=1', []],
                [['q.modDate', 'ASC']],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered($filters, $offset, $limit, $sort);
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetFilteredSortOptions(): void
    {
        $this->entityManager->expects($this->never())->method('getClassMetadata');
        $sortOptions = [
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'modDate desc' => [['q.modDate', 'DESC']],
            'lastDate asc' => [['q.lastDate', 'ASC']],
            'lastDate desc' => [
                ['CASE WHEN q.lastDate IS NULL THEN 1 ELSE 0 END', 'DESC'],
                ['q.lastDate', 'DESC'],
            ],
            'default' => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
        ];
        foreach ($sortOptions as $key => $sortOption) {
            $paginator = $this->createStub(Paginator::class);
            $this->repository->expects($this->atLeast(1))
                ->method('getAll')
                ->willReturn($paginator);
            $result = $this->repository->getFiltered([], 0, 5, $key);
            $this->assertInstanceOf(Paginator::class, $result);
        }
    }

    public function testGetSeriesUsingImage(): void
    {
        $image = $this->createStub(Image::class);
        $image->method('getFilename')->willReturn('image.png');

        $paginator = $this->createStub(Paginator::class);

        $this->repository->expects($this->once())->method('getAll')->willReturn($paginator);
        $this->entityManager->expects($this->never())->method('getClassMetadata');

        $result = $this->repository->getSeriesUsingImage($image);
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetSeriesByPostThrowsNonUniqueResultException(): void
    {
        $page = $this->createStub(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createStub(Query::class);
        $query->method('getOneOrNullResult')->willThrowException(new NonUniqueResultException());

        $qb = $this->mockQueryBuilder($query);
        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->entityManager->expects($this->never())->method('getClassMetadata');

        $this->expectException(NonUniqueResultException::class);
        $this->repository->getSeriesByPost($page);
    }

    /**
     * Helper to create a mocked QueryBuilder returning a given query
     */
    private function mockQueryBuilder(AbstractQuery $query): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(0))
            ->method('select')->willReturnSelf();
        $qb->expects($this->atLeast(0))
            ->method('leftJoin')->willReturnSelf();
        $qb->expects($this->atLeast(0))
            ->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(0))
            ->method('setParameter')->willReturnSelf();
        $qb->expects($this->atLeast(0))
            ->method('getQuery')->willReturn($query);

        return $qb;
    }
}
