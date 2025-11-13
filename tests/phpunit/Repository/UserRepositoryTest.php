<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    'q.isRemoved = \'0\'',
                    [],
                ],
                [['q.displayName', 'ASC']]
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
                    'q.isRemoved = \'0\' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )',
                    [
                        'keyword' => '%test%',
                    ],
                ],
                [['q.displayName', 'ASC']]
            )
            ->willReturn($paginator);
        $result = $this->repository->getFiltered(['keyword' => 'test'], 0, 25);
        $this->assertEquals($paginator, $result);
    }
}
