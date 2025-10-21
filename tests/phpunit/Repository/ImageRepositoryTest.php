<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ImageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(ImageRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testRemoveCallsEntityManagerMethods(): void
    {
        $image = new Image();

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($image);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->remove($image);
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
                [['q.title', 'ASC']]
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
                    '(q.altText LIKE :keyword OR q.title LIKE :keyword OR q.description LIKE :keyword )',
                    [
                        'keyword' => '%test%',
                    ],
                ],
                [['q.title', 'ASC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered(['keyword' => 'test'], 0, 25);
        $this->assertEquals($paginator, $result);
    }

    public function testSortByTitleDesc(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.title', 'DESC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'title desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByCreateDateAsc(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.createDate', 'ASC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'createDate asc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByCreateDateDesc(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.createDate', 'DESC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'createDate desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByModDateDesc(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.modDate', 'DESC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'modDate desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByModDateAsc(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.modDate', 'ASC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'modDate asc');
        $this->assertEquals($paginator, $result);
    }
}
