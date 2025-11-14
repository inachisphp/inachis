<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Entity\User;
use App\Repository\WasteRepository;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class WasteRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    public function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
    }

    public function testDeleteWasteByUser()
    {
        $repository = $this->getMockBuilder(WasteRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods([ 'getEntityManager', 'createQueryBuilder' ])
            ->addMethods([ 'getRepository' ])
            ->getMock();
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->method('execute')->willReturn(3);
        $qb->method('getQuery')->willReturn($query);

        $repository->method('createQueryBuilder')->willReturn($qb);
        $user = new User();
        $result = $repository->deleteWasteByUser($user);
        $this->assertIsInt($result);
    }
}
