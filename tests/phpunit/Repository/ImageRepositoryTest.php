<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\Image;
use Inachis\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ImageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(ImageRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll' ])
            ->getMock();

        $this->repository->expects($this->atLeast(0))
            ->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testRemoveCallsEntityManagerMethods(): void
    {
        $image = new Image();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($image);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->remove($image);
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

    public function testDetermineOrderBy(): void
    {
        $this->entityManager->expects($this->never())->method('getRepository');
        $orders = [
            'title desc' => ['q.title', 'DESC'],
            'createDate asc' => ['q.createDate', 'ASC'],
            'createDate desc' => ['q.createDate', 'DESC'],
            'filesize asc' => ['q.filesize', 'ASC'],
            'filesize desc' => ['q.filesize', 'DESC'],
            'modDate asc' => ['q.modDate', 'ASC'],
            'modDate desc' => ['q.modDate', 'DESC'],
            'default' => ['q.title', 'ASC'],
        ];
        $reflection = new ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');
        $method->setAccessible(true);
        foreach($orders as $key => $order) {
            $this->assertEquals($order, $method->invokeArgs($this->repository, [$key]));
        }
    }
}
