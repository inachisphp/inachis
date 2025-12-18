<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Download;
use App\Repository\DownloadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class DownloadRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(DownloadRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll' ])
            ->getMock();

        $this->repository->expects($this->atLeast(0))
            ->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testRemoveCallsEntityManagerMethods(): void
    {
        $download = new Download();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($download);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->remove($download);
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
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
