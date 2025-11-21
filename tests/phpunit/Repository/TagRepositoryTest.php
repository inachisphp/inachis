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
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = Tag::class;
        $this->entityManager->method('getClassMetadata')
            ->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll', 'createQueryBuilder', ])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testCreateFromAbstract(): void
    {
        $result = $this->repository->create(['title' => 'test-tag']);
        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals('test-tag', $result->getTitle());
    }

    public function testHydrateNonObject(): void
    {
        $this->assertEquals(
            ['title' => 'test-tag'],
            $this->repository->hydrate(['title' => 'test-tag'], ['title' => 'new-tag'])
        );
    }

    public function testGetAllCount(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn(3);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getAllCount([
            'q.title = :title',
            ['title' => 'test-tag']
        ]);
        $this->assertIsInt($result);
        $this->assertEquals(3, $result);
    }

    public function testGetAll(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $query = $this->createMock(Query::class);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
//        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('addGroupBy')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getAll(
            50,
            25,
            [
                'q.title = :title',
                [ 'title' => 'test-tag' ]
            ],
            'q.title',
            'title',
        );
        $this->assertEquals(new Paginator($qb, false), $result);
    }

    public function testGetMaxItemsToShow(): void
    {
        $this->assertEquals(10, $this->repository->getMaxItemsToShow());
    }

    public function testFindByTitleLike(): void
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
