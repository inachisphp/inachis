<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Waste;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Waste|null find($id, $lockMode = null, $lockVersion = null)
 * @method Waste|null findOneBy(array $criteria, array $orderBy = null)
 * @method Waste[]    findAll()
 * @method Waste[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WasteRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Waste::class);
    }

    public function deleteWasteByUser(User $user): void
    {
        $this->createQueryBuilder('w')
            ->delete()
            ->where('w.user = :user')
            ->setParameter('user', $user);
    }
}
