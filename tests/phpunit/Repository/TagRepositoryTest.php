<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class TagRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(TagRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'getEntityManager', 'getAll' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();

        $this->repository->method('getEntityManager')->willReturn($this->entityManager);
        parent::setUp();
    }

    public function testFindByTitleLike(): void
    {
        $paginator = $this->createMock(Paginator::class);
        $this->repository->expects($this->once())
            ->method('getAll')
            ->with(
                0,
                25,
                [
                    'q.title LIKE :title',
                    ['title' => '%test%'],
                ],
                'q.title'
            )
            ->willReturn($paginator);

        $result = $this->repository->findByTitleLike('test');
        $this->assertEquals($paginator, $result);
    }
}
