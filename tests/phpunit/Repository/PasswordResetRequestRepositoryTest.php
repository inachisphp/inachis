<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Repository;

use Inachis\Entity\PasswordResetRequest;
use Inachis\Entity\User;
use Inachis\Repository\PasswordResetRequestRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class PasswordResetRequestRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;

    public function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->repository = $this->getMockBuilder(PasswordResetRequestRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'createQueryBuilder' ])
            ->getMock();
    }

    public function testFindActiveByUser()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atMost(1))->method('where')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getResult')->willReturn([$passwordHash]);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findActiveByUser($user);
        $this->assertEquals([$passwordHash], $result);
    }

    public function testFindLatestActiveForUser(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($passwordHash);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findLatestActiveForUser($user);
        $this->assertEquals($passwordHash, $result);
    }

    public function testFindLatestActiveByHash(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->atLeast(1))->method('andWhere')->willReturnSelf();
        $qb->expects($this->atLeast(1))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())->method('setMaxResults')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($passwordHash);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findLatestActiveByHash('token-hash');
        $this->assertEquals($passwordHash, $result);
    }

    public function testPurgeExpiredHashes(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('delete')->willReturnSelf();
        $qb->expects($this->once())->method('andWhere')->willReturnSelf();
        $qb->expects($this->once())->method('setParameter')->willReturnSelf();
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute')->willReturn(5);
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->purgeExpiredHashes();
        $this->assertEquals(5, $result);
    }
}
