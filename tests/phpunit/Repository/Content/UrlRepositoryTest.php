<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Repository\Content\UrlRepository;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

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
            ->willReturn($this->entityManager);

        $metadata = new ClassMetadata(Url::class);

        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->repository = $this->getMockBuilder(UrlRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([
                'getClassName',
                'getEntityManager',
                'getAll',
                'findOneBy',
            ])
            ->getMock();

        $this->repository
            ->method('getClassName')
            ->willReturn(Url::class);

        $this->repository
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

    public function testGetDefaultUrl(): void
    {
        $page = (new Page())->setTitle('test');

        $url = (new Url($page))
            ->setDefault(true)
            ->setLink('/test');

        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'content' => $page,
                'default' => true,
            ])
            ->willReturn($url);

        $result = $this->repository->getDefaultUrl($page);

        $this->assertSame($url, $result);
    }

    public function testFindSimilarUrlsExcludingId(): void
    {
        $expectedUrl = $this->createStub(Url::class);
        $uuid = Uuid::uuid1()->toString();

        $query = $this->createMock(Query::class);

        $query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn([$expectedUrl]);

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'select',
                'where',
                'setParameter',
                'getQuery',
                'expr',
                'setMaxResults',
            ])
            ->getMock();

        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('u.link')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn(new Expr());

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->atLeastOnce())
            ->method('setParameter')
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();

        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = new class($queryBuilder) extends UrlRepository {
            public function __construct(
                private readonly QueryBuilder $queryBuilder,
            ) {
                // intentionally no parent constructor
            }

            public function createQueryBuilder(
                string $alias,
                ?string $indexBy = null,
            ): QueryBuilder {
                return $this->queryBuilder;
            }
        };

        $result = $repository->findSimilarUrlsExcludingId(
            'test',
            $uuid,
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame([$expectedUrl], $result);
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [
                    ['substring(q.link, 1, 10)', 'asc'],
                    ['q.default', 'desc'],
                    ['q.createdAt', 'desc'],
                ],
                [],
                [
                    ['join', 'q.content', 'p'],
                ],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered([], 0, 25);

        $this->assertSame($paginator, $result);
    }

    public function testGetFilteredWithKeyword(): void
    {
        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    '(p.title LIKE :keyword OR q.link LIKE :keyword)',
                    [
                        'keyword' => '%test%',
                    ],
                ],
                [
                    ['substring(q.link, 1, 10)', 'asc'],
                    ['q.default', 'desc'],
                    ['q.createdAt', 'desc'],
                ],
                [],
                [
                    ['join', 'q.content', 'p'],
                ],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered(
            ['keyword' => 'test'],
            0,
            25,
        );

        $this->assertSame($paginator, $result);
    }

    public function testDetermineOrderBy(): void
    {
        $orders = [
            'contentDate desc' => [
                ['substring(q.link, 1, 10)', 'desc'],
                ['q.default', 'desc'],
                ['q.createdAt', 'desc'],
            ],
            'link asc' => [
                ['q.link', 'ASC'],
            ],
            'link desc' => [
                ['q.link', 'DESC'],
            ],
            'content asc' => [
                ['p.title', 'ASC'],
            ],
            'content desc' => [
                ['p.title', 'DESC'],
            ],
            'default' => [
                ['substring(q.link, 1, 10)', 'asc'],
                ['q.default', 'desc'],
                ['q.createdAt', 'desc'],
            ],
        ];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');

        foreach ($orders as $key => $order) {
            $this->assertEquals(
                $order,
                $method->invokeArgs($this->repository, [$key]),
            );
        }
    }
}
