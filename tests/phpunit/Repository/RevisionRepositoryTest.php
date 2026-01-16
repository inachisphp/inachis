<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\Page;
use Inachis\Entity\Revision;
use Inachis\Repository\RevisionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RevisionRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testHydrateNewRevisionFromPage()
    {
        $this->repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder', 'getNextVersionNumberForPageId' ])
            ->getMock();
        $this->repository->expects($this->atMost(1))
            ->method('getEntityManager')->willReturn($this->entityManager);

        $uuid = Uuid::uuid1();
        $date = new DateTime('now');
        $page = new Page('test page', 'some content');
        $page->setId($uuid)->setSubTitle('sub-title')->setModDate($date);
        $revision = new Revision();
        $revision->setVersionNumber(2)
            ->setTitle('test page')
            ->setSubTitle('sub-title')
            ->setContent('some content')
            ->setUser(null)
            ->setModDate($date)
            ->setPageId($uuid);
        $this->repository->expects($this->once())
            ->method('getNextVersionNumberForPageId')
            ->with($uuid)
            ->willReturn(2);
        $result = $this->repository->hydrateNewRevisionFromPage($page);
        $this->assertEquals($revision, $result);

    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testGetNextVersionNumberForPageId(): void
    {
        $uuid = Uuid::uuid1();
        $this->repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder' ])
            ->getMock();
        $this->repository->expects($this->atMost(1))
            ->method('getEntityManager')->willReturn($this->entityManager);

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(1);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->getNextVersionNumberForPageId($uuid);
        $this->assertEquals(2, $result);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteAndRecordByPage(): void
    {
        $uuid = Uuid::uuid1();
        $date = new DateTime('now');
        $page = new Page('test page', 'some content');
        $page->setId($uuid)->setSubTitle('sub-title')->setModDate($date);

        $this->repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder' ])
            ->getMock();
        $this->repository->expects($this->atMost(1))
            ->method('getEntityManager')->willReturn($this->entityManager);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('delete')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();

        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->deleteAndRecordByPage($page);
        $this->assertEquals(RevisionRepository::DELETED, $result->getAction());
        $this->assertEquals('test page', $result->getTitle());
        $this->assertEquals('sub-title', $result->getSubTitle());
    }
}
