<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Repository\SeriesRepository;
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
        $registry = $this->createMock(ManagerRegistry::class);
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

        $this->entityManager->expects($this->once())->method('remove')->with($series);
        $this->entityManager->expects($this->once())->method('flush');

        $this->repository->remove($series);
    }

    public function testGetSeriesByPostReturnsSeries(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $qb = $this->mockQueryBuilder($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getSeriesByPost($page);
        $this->assertInstanceOf(Series::class, $result);
    }

    public function testGetSeriesByPostReturnsNullWhenNotFound(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(null);

        $qb = $this->mockQueryBuilder($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getSeriesByPost($page);
        $this->assertNull($result);
    }

    public function testGetPublishedSeriesByPost(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $qb = $this->mockQueryBuilder($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublishedSeriesByPost($page);
        $this->assertInstanceOf(Series::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPublishedSeriesByYearAndUrl(): void
    {
        $year = '2025';
        $url = 'my-series';

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(new Series());

        $expr = $this->createMock(Expr::class);
        $expr->method('like')->willReturn($this->createMock(Comparison::class));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $qb->method('expr')->willReturn($expr);

        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublishedSeriesByYearAndUrl($year, $url);
        $this->assertInstanceOf(Series::class, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPublishedSeriesByYearAndUrlReturnsNull(): void
    {
        $year = '2025';
        $url = 'non-existent';

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(null);

        $expr = $this->createMock(Expr::class);
        $expr->method('like')->willReturn($this->createMock(Comparison::class));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $qb->method('expr')->willReturn($expr);

        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getPublishedSeriesByYearAndUrl($year, $url);
        $this->assertNull($result);
    }

    public function testGetFilteredWithKeyword(): void
    {
        $filters = ['keyword' => 'test'];
        $offset = 0;
        $limit = 10;
        $sort = 'modDate asc';

        $paginator = $this->createMock(Paginator::class);

        $this->repository->method('getAll')
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
        $filters = [];
        $offset = 0;
        $limit = 5;
        $sort = 'modDate asc';

        $paginator = $this->createMock(Paginator::class);

        $this->repository->method('getAll')
            ->with(
                $offset,
                $limit,
                [],
                [['q.modDate', 'ASC']],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered($filters, $offset, $limit, $sort);
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetFilteredSortOptions(): void
    {

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
            $paginator = $this->createMock(Paginator::class);
            $this->repository->method('getAll')
                ->willReturn($paginator);
            $result = $this->repository->getFiltered([], 0, 5, $key);
            $this->assertInstanceOf(Paginator::class, $result);
        }
    }

    public function testGetSeriesUsingImage(): void
    {
        $image = $this->createMock(Image::class);
        $image->method('getFilename')->willReturn('image.png');

        $paginator = $this->createMock(Paginator::class);

        $this->repository->method('getAll')->willReturn($paginator);

        $result = $this->repository->getSeriesUsingImage($image);
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetSeriesByPostThrowsNonUniqueResultException(): void
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn(Uuid::uuid1());

        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willThrowException(new NonUniqueResultException());

        $qb = $this->mockQueryBuilder($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $this->expectException(NonUniqueResultException::class);
        $this->repository->getSeriesByPost($page);
    }

    /**
     * Helper to create a mocked QueryBuilder returning a given query
     */
    private function mockQueryBuilder(AbstractQuery $query): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }
}
