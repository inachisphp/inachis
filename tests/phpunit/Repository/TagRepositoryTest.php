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
            ->onlyMethods([ 'getEntityManager', 'getAll', ])
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

//    public function testGetAllCount():  void
//    {
//        $result = $this->repository->getAllCount();
//    }

//    public function testGetAll(): void
//    {
//        $qb = $this->createMock(QueryBuilder::class);
//        $qb->method('select')->willReturnSelf();
//        $qb->method('from')->willReturnSelf();
//        $qb->method('join')->willReturnSelf();
//        $qb->method('where')->willReturnSelf();
//        $qb->method('addOrderBy')->willReturnSelf();
//        $qb->method('setParameters')->willReturnSelf();
//        $qb->method('addGroupBy')->willReturnSelf();
//        $qb->method('setFirstResult')->willReturnSelf();
//        $qb->method('setMaxResults')->willReturnSelf();
//
//        $query = $this->createMock(AbstractQuery::class);
//
//        $tag = new Tag('test-tag');
//        $qb->method('getQuery')->willReturn($query);
//
//        $this->repository->method('createQueryBuilder')->willReturn($qb);
//        $result = $this->repository->getAll(
//            0,
//            25,
//            [
//                'something',
//                [ 'foo' => 'bar', ]
//            ],
//            [
//                [ 'orderColumn', 'ASC', ]
//            ],
//            [
//                'groupBy',
//            ],
//            [
//                'join', 'join',
//            ]
//        );
//    }

    public function testGetMaxItemsToShow(): void
    {
        $this->assertEquals(10, $this->repository->getMaxItemsToShow());
    }

//    public function testWipeSuccessful(): void
//    {
//        $connection = $this->createMock(Connection::class);
//        $connection->expects($this->once())->method('beginTransaction');
//        $connection->expects($this->exactly(3))->method('query')->withConsecutive(
//            ['SET FOREIGN_KEY_CHECKS=0'],
//            [$this->stringContains('DELETE FROM')],
//            ['SET FOREIGN_KEY_CHECKS=1']
//        );
//        $connection->expects($this->once())->method('commit');
//        $connection->expects($this->never())->method('rollBack');
//
//        $classMetadata = $this->createMock(ClassMetadata::class);
//        $classMetadata->method('getTableName')->willReturn('my_table');
//
//        $entityManager = $this->createMock(EntityManagerInterface::class);
//        $entityManager->method('getConnection')->willReturn($connection);
//        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
//
//        $logger = $this->createMock(LoggerInterface::class);
//        $logger->expects($this->never())->method('error');
//
//        $repo = $this->getMockBuilder(TagRepository::class)
//            ->onlyMethods(['getEntityManager', 'getClassName'])
//            ->getMock();
//        $repo->method('getEntityManager')->willReturn($entityManager);
//        $repo->method('getClassName')->willReturn(new Tag());
//
//        $repo->wipe($logger);
//    }

//    public function testWipeHandlesException()
//    {
//        $connection = $this->createMock(Connection::class);
//        $connection->expects($this->once())->method('beginTransaction');
//        $connection->method('query')->willThrowException(new \Exception('DB error'));
//        $connection->expects($this->once())->method('rollBack');
//        $connection->expects($this->never())->method('commit');
//
//        $classMetadata = $this->createMock(ClassMetadata::class);
//        $classMetadata->method('getTableName')->willReturn('my_table');
//
//        $entityManager = $this->createMock(EntityManagerInterface::class);
//        $entityManager->method('getConnection')->willReturn($connection);
//        $entityManager->method('getClassMetadata')->willReturn($classMetadata);
//
//        $logger = $this->createMock(LoggerInterface::class);
//        $logger->expects($this->once())
//            ->method('error')
//            ->with($this->stringContains('Failed to wipe table'));
//
//        $repo = $this->getMockBuilder(TagRepository::class)
//            ->onlyMethods(['getEntityManager', 'getClassName'])
//            ->getMock();
//        $repo->method('getEntityManager')->willReturn($entityManager);
//        $repo->method('getClassName')->willReturn(new Tag());
//
//        $repo->wipe($logger);
//    }

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
