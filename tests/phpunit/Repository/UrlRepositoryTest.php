<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\Page;
use Inachis\Entity\Url;
use Inachis\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

class UrlRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $registry
            ->method('getManagerForClass')
            ->with(Url::class)
            ->willReturn($this->entityManager);
        $metadata = new ClassMetadata(Url::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->with(Url::class)
            ->willReturn($metadata);
        $this->repository = $this->getMockBuilder(UrlRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getClassName', 'getEntityManager', 'getAll', 'findOneBy' ])
            ->getMock();
        $this->repository->expects($this->atLeast(0))
            ->method('getClassName')
            ->willReturn(Url::class);
        $this->repository->expects($this->atLeast(0))
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
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
        $this->entityManager->expects($this->never())->method('createQueryBuilder');
        $this->repository->expects($this->once())
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
        $expectedUrl = $this->createStub(Url::class);
        $uuid = Uuid::uuid1();

        $query = $this->createMock(Query::class);
        $query->expects($this->atLeast(1))->method('getResult')->willReturn([$expectedUrl]);
        $expr = $this->createStub(Expr::class);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'where', 'setParameter', 'getQuery', 'expr'])
            ->getMock();
        $queryBuilder->expects($this->atLeast(1))->method('select')->willReturnSelf();
        $queryBuilder->expects($this->atLeast(1))->method('where')->willReturnSelf();
        $queryBuilder->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $queryBuilder->expects($this->atLeast(1))->method('expr')->willReturn($expr);
        $queryBuilder->expects($this->atLeast(1))->method('getQuery')->willReturn($query);
        $this->entityManager->expects($this->atLeast(1))->method('createQueryBuilder')->willReturn($queryBuilder);

        $result = $this->repository->findSimilarUrlsExcludingId('test', $uuid);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame([$expectedUrl], $result);
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $paginator = $this->createStub(Paginator::class);
        $this->entityManager->expects($this->never())->method('remove');
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
        $this->entityManager->expects($this->never())->method('remove');
        $paginator = $this->createStub(Paginator::class);
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

    public function testDetermineOrderBy(): void
    {
        $this->entityManager->expects($this->never())->method('remove');
        $this->repository->expects($this->never())->method('findOneBy');
        $orders = [
            'contentDate desc' => [
                [ 'substring(q.link, 1, 10)', 'desc' ],
                [ 'q.default', 'desc' ],
                [ 'q.createDate', 'desc' ],
            ],
            'link asc' => [['q.link', 'ASC']],
            'link desc' => [['q.link', 'DESC']],
            'content asc' => [['p.title', 'ASC']],
            'content desc' => [['p.title', 'DESC']],
            'default' => [
                [ 'substring(q.link, 1, 10)', 'asc' ],
                [ 'q.default', 'desc' ],
                [ 'q.createDate', 'desc' ],
            ]
        ];
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');
        foreach($orders as $key => $order) {
            $this->assertEquals($order, $method->invokeArgs($this->repository, [$key]));
        }
    }
}
