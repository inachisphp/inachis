<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\User\UserRepository;
use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private UserRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['getEntityManager', 'getAll'])
            ->getMock();

        $this->repository
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        parent::setUp();
    }

    public function testGetFilteredWithoutKeyword(): void
    {
        $params = $this->createStub(ContentQueryParameters::class);

        $params->method('getFilters')->willReturn([]);
        $params->method('getLimit')->willReturn(25);
        $params->method('getOffset')->willReturn(0);
        $params->method('getSort')->willReturn('');

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                25,
                0,
                [
                    'q.isRemoved = \'0\'',
                    [],
                ],
                [['q.displayName', 'ASC']],
                ['q.id'],
                [],
            )
            ->willReturn($paginator);

        $this->assertSame(
            $paginator,
            $this->repository->getFiltered($params),
        );
    }

    public function testGetFilteredWithKeyword(): void
    {
        $params = $this->createStub(ContentQueryParameters::class);

        $params->method('getFilters')->willReturn([
            'keyword' => 'test',
        ]);
        $params->method('getLimit')->willReturn(25);
        $params->method('getOffset')->willReturn(0);
        $params->method('getSort')->willReturn('');

        $paginator = $this->createStub(Paginator::class);

        $this->repository
            ->expects($this->once())
            ->method('getAll')
            ->with(
                25,
                0,
                [
                    'q.isRemoved = \'0\' AND (q.displayName LIKE :keyword OR q.username LIKE :keyword OR q.email LIKE :keyword )',
                    [
                        'keyword' => '%test%',
                    ],
                ],
                [['q.displayName', 'ASC']],
                ['q.id'],
                [],
            )
            ->willReturn($paginator);

        $this->assertSame(
            $paginator,
            $this->repository->getFiltered($params),
        );
    }
}
