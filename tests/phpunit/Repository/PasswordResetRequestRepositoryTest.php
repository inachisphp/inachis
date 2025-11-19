<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
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
        $registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->getMockBuilder(PasswordResetRequestRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods([ 'createQueryBuilder' ])
            ->getMock();
    }

    public function testFindActiveByUser()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$passwordHash]);
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findActiveByUser($user);
        $this->assertEquals([$passwordHash], $result);
    }

    public function testFindLatestActiveForUser(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn($passwordHash);
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findLatestActiveForUser($user);
        $this->assertEquals($passwordHash, $result);
    }

    public function testFindLatestActiveByHash(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();

        $user = new User();
        $date = new DateTimeImmutable('now');
        $passwordHash = new PasswordResetRequest($user, 'token-hash', $date);
        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn($passwordHash);
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->findLatestActiveByHash('token-hash');
        $this->assertEquals($passwordHash, $result);
    }

    public function testPurgeExpiredHashes(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn(5);
        $qb->method('getQuery')->willReturn($query);

        $this->repository->method('createQueryBuilder')->willReturn($qb);
        $result = $this->repository->purgeExpiredHashes();
        $this->assertEquals(5, $result);
    }
}
