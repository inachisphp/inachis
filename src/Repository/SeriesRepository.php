<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\Series;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * @param Series $series
     */
    public function remove(Series $series)
    {
        $this->getEntityManager()->remove($series);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getSeriesByPost(string $page): mixed
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.items', 'Series_pages')
            ->where('Series_pages.id = :pageId')
            ->setParameter('pageId', $page->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getPublishedSeriesByPost(string $page)
    {
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->select('s')
            ->leftJoin('s.items', 'Series_pages')
            ->where(
//                $qb->expr()->andX(
                    'Series_pages.id = :pageId' //,
//                    's.items.status = \'published\''
//                )
            )
            ->setParameter('pageId', $page->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getSeriesByYearAndUrl($year, $url)
    {
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->select('s')
            ->where($qb->expr()->like('s.lastDate', ':year'))
            ->andWhere($qb->expr()->like('s.url', ':url'))
            ->setParameters([
                'year' => $year . '%',
                'url' => $url,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}
