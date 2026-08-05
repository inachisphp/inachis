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
use Inachis\Entity\Media\Image;
use Inachis\Repository\Media\ImageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class ImageRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $cache = $this->createStub(CacheInterface::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(ImageRepository::class)
            ->setConstructorArgs([$cache, $registry])
            ->onlyMethods(['getEntityManager', 'getAll'])
            ->getMock();

        $this->repository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

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
        $this->entityManager
            ->expects($this->never())
            ->method('getRepository');

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    '1=1',
                    [],
                ],
                [
                    ['q.title', 'ASC'],
                ],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered([], 0, 25);

        $this->assertSame($paginator, $result);
    }

    public function testGetFilteredWithKeyword(): void
    {
        $this->entityManager
            ->expects($this->never())
            ->method('getRepository');

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    '1=1 AND (q.altText LIKE :keyword OR q.title LIKE :keyword OR q.description LIKE :keyword )',
                    [
                        'keyword' => '%test%',
                    ],
                ],
                [
                    ['q.title', 'ASC'],
                ],
            )
            ->willReturn($paginator);

        $result = $this->repository->getFiltered(
            ['keyword' => 'test'],
            0,
            25
        );

        $this->assertSame($paginator, $result);
    }

    public function testDetermineOrderBy(): void
    {
        $orders = [
            'title desc'     => ['q.title', 'DESC'],
            'createdAt asc'  => ['q.createdAt', 'ASC'],
            'createdAt desc' => ['q.createdAt', 'DESC'],
            'filesize asc'   => ['q.filesize', 'ASC'],
            'filesize desc'  => ['q.filesize', 'DESC'],
            'updatedAt asc'  => ['q.updatedAt', 'ASC'],
            'updatedAt desc' => ['q.updatedAt', 'DESC'],
            'default'        => ['q.title', 'ASC'],
        ];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');

        foreach ($orders as $sort => $expected) {
            $this->assertEquals(
                $expected,
                $method->invoke($this->repository, $sort)
            );
        }
    }
}
