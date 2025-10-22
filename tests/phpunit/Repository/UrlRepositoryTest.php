<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Page;
use App\Entity\Url;
use App\Repository\UrlRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UrlRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $registry
            ->method('getManagerForClass')
            ->with(Url::class)
            ->willReturn($this->entityManager);
        $metadata = $this->createMock(ClassMetadata::class);
        $this->entityManager
            ->method('getClassMetadata')
            ->with(Url::class)
            ->willReturn($metadata);
        $this->repository = $this->getMockBuilder(UrlRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll', 'findOneBy' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();
        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testRemoveCallsEntityManagerMethods(): void
    {
        $url = new Url(new Page());

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($url);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->remove($url);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultUrl(): void
    {
        $page = (new Page())->setTitle('test');
        $url = (new Url($page))->setDefault(true)->setLink('/test');
        $this->repository
            ->method('findOneBy')
            ->with([ 'content' => $page, 'default' => true, ])
            ->willReturn($url);
        $result = $this->repository->getDefaultUrl($page);
        $this->assertEquals($url, $result);
    }

    /**
     * @throws Exception
     */
    public function testFindSimilarUrlsExcludingId(): void
    {
        $expectedUrl = $this->createMock(Url::class);
        $uuid = Uuid::uuid1();

        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn([$expectedUrl]);
        $expr = $this->createMock(Expr::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'where', 'setParameters', 'getQuery', 'expr'])
            ->getMock();
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameters')->willReturnSelf();
        $queryBuilder->method('expr')->willReturn($expr);
        $queryBuilder->method('getQuery')->willReturn($query);
        $this->entityManager->method('createQueryBuilder')->willReturn($queryBuilder);

        $result = $this->repository->findSimilarUrlsExcludingId('test', $uuid);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame([$expectedUrl], $result);
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [
                    [ 'substring(q.link, 1, 10)', 'asc' ],
                    [ 'q.default', 'desc' ],
                    [ 'q.createDate', 'desc' ],
                ]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25);
        $this->assertEquals($paginator, $result);
    }

    public function testGetFilteredWithKeyword(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    '(p.title LIKE :keyword OR q.link LIKE :keyword)',
                    [
                        'keyword' => '%test%',
                    ]
                ],
                [
                    [ 'substring(q.link, 1, 10)', 'asc' ],
                    [ 'q.default', 'desc' ],
                    [ 'q.createDate', 'desc' ],
                ]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([ 'keyword' => 'test' ], 0, 25);
        $this->assertEquals($paginator, $result);
    }
}
