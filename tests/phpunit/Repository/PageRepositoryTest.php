<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Tag;
use App\Entity\Url;
use App\Repository\PageRepository;
use App\Repository\UrlRepository;
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
 * @covers \App\Repository\PageRepository
 * @uses \App\Entity\Page
 * @uses \App\Entity\Category
 * @uses \App\Entity\Tag
 * @uses \App\Entity\Url
 * @uses \App\Entity\Image
 */
class PageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private PageRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(PageRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'createQueryBuilder', 'getAll'])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testGetMaxItemsToShow(): void
    {
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
        $urlRepository
            ->expects($this->once())
            ->method('remove')
            ->with($url);
        $this->entityManager
            ->method('getRepository')
            ->with(Url::class)
            ->willReturn($urlRepository);
        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($page);
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        $this->repository->remove($page);
    }

    public function testGetPagesWithCategory(): void
    {
        $page = new Page('test page');
        $category = new Category();
        $page->addCategory($category);
        $expr = $this->createMock(Expr::class);
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn($page);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $qb->method('expr')->willReturn($expr);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
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
        $category = new Category('test category');
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn(2);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $this->repository->method('createQueryBuilder')->willReturn($qb);

        $this->assertEquals(2, $this->repository->getPagesWithCategoryCount($category));
    }

    public function testGetPagesWithTag(): void
    {
        $page = new Page('test page');
        $tag = new Tag('test');
        $page->addTag($tag);
        $expr = $this->createMock(Expr::class);
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn([$page]);
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('expr')->willReturn($expr);
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
        $this->assertEquals(
            [$page],
            $this->repository->getPagesWithTag($tag, 10, 20)
        );
    }

    public function testGetAllOfTypeByPostDate(): void
    {
        $paginator = $this->createMock(Paginator::class);
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
        $filters = [
            'status' => 1,
            'visibility' => 1,
            'keyword' => 'test',
            'excludeIds' => 42,
        ];
        $paginator = $this->createMock(Paginator::class);
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
        $paginator = $this->createMock(Paginator::class);
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
        $image = new Image();
        $image->setId(Uuid::uuid1());
        $image->setFilename('test.jpeg');
        $paginator = $this->createMock(Paginator::class);
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
