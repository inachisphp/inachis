<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class CategoryRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(CategoryRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'createQueryBuilder', 'getAll'])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
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
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('q.parent is null')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn(['mock_result']);

        $this->repository->method('createQueryBuilder')->willReturn($queryBuilder);
        $result = $this->repository->getRootCategories();
        $this->assertEquals(['mock_result'], $result);
    }

    public function testFindByTitleLikeDelegatesToGetAll(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    'q.title LIKE :title',
                    ['title' => '%test%'],
                ],
                'q.title'
            )
            ->willReturn($paginator);

        $result = $this->repository->findByTitleLike('test');
        $this->assertEquals($paginator, $result);
    }
}
