<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Media;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Media\Download;
use Inachis\Repository\Media\DownloadRepository;
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
            ->onlyMethods(['getEntityManager', 'getAll'])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
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
                [['q.title', 'ASC']],
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
                [['q.title', 'ASC']],
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
                [['q.title', 'DESC']],
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'title desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByCreatedAtAsc(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.createdAt', 'ASC']],
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'createdAt asc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByCreatedAtDesc(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.createdAt', 'DESC']],
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'createdAt desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByUpdatedAtDesc(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.updatedAt', 'DESC']],
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'updatedAt desc');
        $this->assertEquals($paginator, $result);
    }

    public function testSortByUpdatedAtAsc(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $paginator = $this->createStub(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [],
                [['q.updatedAt', 'ASC']],
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered([], 0, 25, 'updatedAt asc');
        $this->assertEquals($paginator, $result);
    }
}
