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
use App\Repository\PageRepositoryInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    public function testRemove(): void
    {
        $page = new Page();
        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($page);
        $this->entityManager
            ->expects($this->once())
            ->method('flush');
        $this->repository->remove($page);
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
}
