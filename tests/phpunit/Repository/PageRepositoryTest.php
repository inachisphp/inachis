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
        $this->repository = new PageRepository($registry);
    }

    public function testGetMaxItemsToShow(): void
    {
        $this->assertEquals(10, $this->repository->getMaxItemsToShow());
    }

//    public function testGetFilteredOfTypeByPostDate(): void
//    {
//        dump($this->repository->getFilteredOfTypeByPostDate([], 'post', 0, 25));
//    }

//    public function testRemove(): void
//    {
//        $repository = $this->getMockBuilder(PageRepository::class)
//            ->setConstructorArgs([$this->registry])
//            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder' ])
//            ->getMock();
//        $qb = $this->createMock(QueryBuilder::class);
//        $qb->method('remove')->willReturnSelf();
//        $qb->method('flush')->willReturnSelf();
//
//        $repository->method('createQueryBuilder')->willReturn($qb);
//        $result = $repository->remove(new Page());
//        $this->assertIsInt($result);
//    }


}
