<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Page;
use App\Entity\Revision;
use App\Entity\User;
use App\Repository\RevisionRepository;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Rfc4122\UuidV1;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RevisionRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testHydrateNewRevisionFromPage()
    {
        $this->repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder', 'getNextVersionNumberForPageId' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);


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
            ->addMethods([ 'getRepository' ])
            ->getMock();
        $this->repository->method('getEntityManager')->willReturn($this->entityManager);

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getSingleScalarResult')->willReturn(1);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);

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
            ->addMethods([ 'getRepository' ])
            ->getMock();
        $this->repository->method('getEntityManager')->willReturn($this->entityManager);

        $query = $this->createMock(AbstractQuery::class);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $result = $this->repository->deleteAndRecordByPage($page);
        $this->assertEquals(RevisionRepository::DELETED, $result->getAction());
        $this->assertEquals('test page', $result->getTitle());
        $this->assertEquals('sub-title', $result->getSubTitle());
    }
}
