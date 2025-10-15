<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\LoginActivity;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LoginActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginActivity[]    findAll()
 * @method LoginActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginActivityRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginActivity::class);
    }
}
