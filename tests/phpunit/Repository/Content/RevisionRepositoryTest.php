<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Repository\Content\RevisionRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class RevisionRepositoryTest extends TestCase
{
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createStub(ManagerRegistry::class);
    }

    public function testHydrateNewRevisionFromPage(): void
    {
        $page = new Page('Test page', 'Some content');
        $page->setSubTitle('Sub-title');

        $repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['getNextVersionNumberForPage'])
            ->getMock();

        $repository->expects($this->once())
            ->method('getNextVersionNumberForPage')
            ->with($page)
            ->willReturn(2);

        $revision = $repository->hydrateNewRevisionFromPage($page);

        $this->assertInstanceOf(Revision::class, $revision);
        $this->assertSame($page, $revision->getPage());
        $this->assertSame(2, $revision->getVersionNumber());
        $this->assertSame('Test page', $revision->getTitle());
        $this->assertSame('Sub-title', $revision->getSubTitle());
        $this->assertSame('Some content', $revision->getContent());
        $this->assertSame($page->getAuthor(), $revision->getUser());
    }

    public function testGetNextVersionNumberForPage(): void
    {
        $page = new Page('Test page', 'Some content');

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(1);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('MAX(r.versionNumber) as max_version')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('r.page = :page')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('page', $page)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);

        $this->assertSame(2, $repository->getNextVersionNumberForPage($page));
    }

    public function testGetNextVersionNumberForPageReturnsOneWhenNoPreviousRevisionExists(): void
    {
        $page = new Page('Test page', 'Some content');

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('MAX(r.versionNumber) as max_version')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('r.page = :page')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('page', $page)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);

        $this->assertSame(1, $repository->getNextVersionNumberForPage($page));
    }

    public function testGetRevisionsForPage(): void
    {
        $page = new Page('Test page', 'Some content');

        $revisionOne = new Revision();
        $revisionTwo = new Revision();

        $results = [$revisionOne, $revisionTwo];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($results);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('r.page = :pageId')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('pageId', $page->getId(), 'uuid_binary')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('r.versionNumber', 'DESC')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(25)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);

        $this->assertSame(
            $results,
            $repository->getRevisionsForPage($page)
        );
    }

    public function testDeleteAndRecordByPage(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $page = new Page('Test page', 'Some content');
        $page->setSubTitle('Sub-title');

        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(1);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('r.page = :page')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('page', $page)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->getMockBuilder(RevisionRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();

        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('r')
            ->willReturn($queryBuilder);

        $repository->expects($this->exactly(2))
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Revision::class));

        $entityManager->expects($this->once())
            ->method('flush');

        $revision = $repository->deleteAndRecordByPage($page, null);

        $this->assertInstanceOf(Revision::class, $revision);
        $this->assertNull($revision->getPage());
        $this->assertSame('Test page', $revision->getTitle());
        $this->assertSame('Sub-title', $revision->getSubTitle());
        $this->assertNull($revision->getUser());
        $this->assertSame(RevisionRepository::DELETED, $revision->getAction());
    }
}
