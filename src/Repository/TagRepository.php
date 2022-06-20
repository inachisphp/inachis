<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @param $title
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function findByTitleLike($title)
    {
        return $this->getAll(
            0,
            25,
            [
                'q.title LIKE :title',
                [
                    'title' => '%' . $title . '%',
                ],
            ],
            'q.title'
        );
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('t')
            ->where('t.something = :value')->setParameter('value', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */
}
