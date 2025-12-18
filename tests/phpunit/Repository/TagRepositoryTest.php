<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class TagRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->name = Tag::class;
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll', 'getClassName', 'createQueryBuilder', ])
            ->getMock();
        $this->repository->expects($this->atLeast(0))
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
        $this->repository->expects($this->atLeast(0))
            ->method('getClassName')
            ->willReturn(Tag::class);
        parent::setUp();
    }

    public function testCreateFromAbstract(): void
    {
        $this->entityManager->expects($this->never())->method('createQueryBuilder');
        $result = $this->repository->create(['title' => 'test-tag']);
        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals('test-tag', $result->getTitle());
    }

    public function testHydrateNonObject(): void
    {
        $this->entityManager->expects($this->never())->method('createQueryBuilder');
        $this->assertEquals(
            ['title' => 'test-tag'],
            $this->repository->hydrate(['title' => 'test-tag'], ['title' => 'new-tag'])
        );
    }

    public function testGetAllCount(): void
    {
        $query = $this->createStub(Query::class);
        $query->method('getSingleScalarResult')->willReturn(3);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('select')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('from')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('getQuery')->willReturn($query);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $this->repository->expects($this->once())
            ->method('getEntityManager')->willReturn($this->entityManager);

        $result = $this->repository->getAllCount([
            'q.title = :title',
            ['title' => 'test-tag']
        ]);
        $this->assertIsInt($result);
        $this->assertEquals(3, $result);
    }

    public function testGetAll(): void
    {
        $query = $this->createStub(Query::class);
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('from')->willReturnSelf();
        $qb->expects($this->once())->method('join')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('addOrderBy')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('addGroupBy')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->atLeast(0))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getClassName', 'createQueryBuilder', ])
            ->getMock();

        $this->repository->expects($this->once())
            ->method('getClassName')->willReturn(Tag::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $this->repository->expects($this->once())
            ->method('getEntityManager')->willReturn($this->entityManager);

        $result = $this->repository->getAll(
            50,
            25,
            [
                'q.title = :title',
                ['title' => 'test-tag']
            ],
            [
                [ 'title', 'asc' ],
            ],
            [ 'id' ],
            [ 'table', 'fields' ],
        );
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetAllOrderByString(): void
    {
        $query = $this->createStub(Query::class);
        $query->method('setFirstResult')->willReturnSelf();
        $query->method('setMaxResults')->willReturnSelf();

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('select')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('from')->willReturnSelf();
        $qb->expects($this->atLeast(0))->method('addOrderBy')->willReturnSelf();
        $qb->expects($this->atLeast(0))->method('getQuery')->willReturn($query);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getClassName', 'createQueryBuilder', ])
            ->getMock();

        $this->repository->expects($this->once())
            ->method('getEntityManager')->willReturn($this->entityManager);
        $this->repository->expects($this->once())
            ->method('getClassName')->willReturn(Tag::class);

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $this->repository->expects($this->once())
            ->method('getEntityManager')->willReturn($this->entityManager);

        $result = $this->repository->getAll(
            50,
            25,
            [],
            'title ASC',
        );
        $this->assertInstanceOf(Paginator::class, $result);
    }

    public function testGetMaxItemsToShow(): void
    {
        $this->entityManager->expects($this->never())->method('createQueryBuilder');
        $this->repository->expects($this->never())->method('getAll');
        $this->assertEquals(10, $this->repository->getMaxItemsToShow());
    }

    public function testFindByTitleLike(): void
    {
        $paginator = $this->createStub(Paginator::class);
        $this->entityManager->expects($this->never())->method('createQueryBuilder');
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
