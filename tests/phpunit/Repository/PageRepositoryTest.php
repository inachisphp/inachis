<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\Category;
use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Tag;
use Inachis\Entity\Url;
use Inachis\Repository\PageRepository;
use Inachis\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

/**
 * @covers \Inachis\Repository\PageRepository
 * @uses \Inachis\Entity\Page
 * @uses \Inachis\Entity\Category
 * @uses \Inachis\Entity\Tag
 * @uses \Inachis\Entity\Url
 * @uses \Inachis\Entity\Image
 */
class PageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private PageRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(PageRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'createQueryBuilder', 'getAll'])
            ->getMock();

        $this->repository->expects($this->atLeast(0))
            ->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testGetMaxItemsToShow(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $this->assertEquals(10, $this->repository->getMaxItemsToShow());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
    public function testRemove(): void
    {
        $page = new Page();
        $url = new Url($page, 'test-link');

        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('remove')
            ->with($url);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepository);
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($page);
        $this->entityManager->expects($this->once())
            ->method('flush');
        $this->repository->remove($page);
    }

    public function testGetPagesWithCategory(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $page = new Page('test page');
        $category = new Category();
        $page->addCategory($category);
        $expr = $this->createStub(Expr::class);
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn($page);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('leftJoin')->willReturnSelf();
        $qb->expects($this->once())->method('where')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $qb->expects($this->once())->method('expr')->willReturn($expr);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $this->assertEquals(
            $page,
            $this->repository->getPagesWithCategory($category, 10, 20)
        );
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetPagesWithCategoryCount(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $category = new Category('test category');
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getSingleScalarResult')->willReturn(2);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('leftJoin')->willReturnSelf();
        $qb->expects($this->atMost(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);
        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $this->assertEquals(2, $this->repository->getPagesWithCategoryCount($category));
    }

    public function testGetPagesWithTag(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $page = new Page('test page');
        $tag = new Tag('test');
        $page->addTag($tag);
        $expr = $this->createStub(Expr::class);
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn([$page]);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('select')->willReturnSelf();
        $qb->expects($this->once())->method('leftJoin')->willReturnSelf();
        $qb->expects($this->atMost(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('expr')->willReturn($expr);
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('setFirstResult')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())->method('createQueryBuilder')->willReturn($qb);
        $this->assertEquals(
            [$page],
            $this->repository->getPagesWithTag($tag, 10, 20)
        );
    }

    public function testGetAllOfTypeByPostDate(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                [
                    'q.type = :type',
                    [
                        'type' => 'post',
                    ],
                ],
                [['q.postDate', 'DESC']]
            )
            ->willReturn($paginator);
        $this->assertEquals(
            $paginator,
            $this->repository->getAllOfTypeByPostDate('post', 10, 5)
        );
    }

    public function testDetermineOrderBy(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $orders = [
            'title asc' => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'modDate asc' => [['q.modDate', 'ASC']],
            'modDate desc' => [['q.modDate', 'DESC']],
            'postDate asc' => [['q.postDate', 'ASC']],
            'default' => [['q.postDate', 'DESC']],
        ];
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');
        $method->setAccessible(true);
        foreach($orders as $key => $order) {
            $this->assertEquals($order, $method->invokeArgs($this->repository, [$key]));
        }
    }

    public function testGetFilteredOfTypeByPostDate(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $filters = [
            'status' => 1,
            'visibility' => 1,
            'keyword' => 'test',
            'excludeIds' => 42,
        ];
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                10,
                5,
                [
                    'q.type = :type AND q.status = :status AND q.visibility = :visibility AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.content LIKE :keyword ) AND q.id NOT IN (:excludeIds)',
                    [
                        'type' => 'post',
                        'status' => 1,
                        'visibility' => 1,
                        'keyword' => '%test%',
                        'excludeIds' => 42,
                    ],
                ],
                [['q.postDate', 'DESC']]
            )
            ->willReturn($paginator);
        $this->assertEquals(
            $paginator,
            $this->repository->getFilteredOfTypeByPostDate($filters, 'post', 10, 5)
        );
    }

    public function testGetFilteredIds(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                0,
                [
                    'q.id IN (:ids)',
                    [
                        'ids' => '1,2,3',
                    ],
                ]
            )
            ->willReturn($paginator);
        $this->assertEquals(
            $paginator,
            $this->repository->getFilteredIds('1,2,3')
        );
    }

    public function testGetPostsUsingImage(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $image = new Image();
        $image->setId(Uuid::uuid1());
        $image->setFilename('test.jpeg');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    'q.content LIKE :filename OR q.featureImage = :image',
                    [
                        'filename' => '%' . $image->getFilename() . '%',
                        'image' => $image->getId(),
                    ],
                ]
            )
            ->willReturn($paginator);
        $this->assertEquals(
            $paginator,
            $this->repository->getPostsUsingImage($image)
        );
    }
}
