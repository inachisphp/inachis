<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Image;
use Inachis\Entity\Page;
use Inachis\Entity\Series;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[] findAll()
 * @method Series[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends AbstractRepository implements SeriesRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * @param Series $series
     */
    public function remove(Series $series): void
    {
        $this->getEntityManager()->remove($series);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getSeriesByPost(Page $page): mixed
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.items', 'Series_pages')
            ->where('Series_pages.id = :pageId')
            ->setParameter('pageId', $page->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getPublishedSeriesByPost(Page $page)
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
    public function getPublicSeriesByYearAndUrl($year, $url)
    {
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->select('s')
            ->where($qb->expr()->like('s.lastDate', ':year'))
            ->andWhere($qb->expr()->like('s.url', ':url'))
            ->andWhere('s.visibility = \'' . Series::PUBLIC . '\'')
            ->setParameter('year', $year . '%')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $filters
     * @param $offset
     * @param $limit
     * @return Paginator
     */
    public function getFiltered(array $filters, int $offset, int $limit, string $sort): Paginator
    {
        $where = [
            '1=1',
            $filters,
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.description LIKE :keyword )';
            $where[1]['keyword'] = '%' . $where[1]['keyword'] . '%';
        }
        if (!empty($filters['visibility'])) {
            $where[0] .= ' AND q.visibility = :visibility';
        }
        $sort = match ($sort) {
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'modDate asc' => [['q.modDate', 'ASC']],
            'modDate desc' => [['q.modDate', 'DESC']],
            'lastDate asc' => [['q.lastDate', 'ASC']],
            'lastDate desc' => [
                ['CASE WHEN q.lastDate IS NULL THEN 1 ELSE 0 END', 'DESC'],
                ['q.lastDate', 'DESC']
            ],
            default => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
        };
        return $this->getAll(
            $offset,
            $limit,
            $where,
            $sort
        );
    }

    /**
     * @param Image $image
     * @return Paginator
     */
    public function getSeriesUsingImage(Image $image): Paginator
    {
        return $this->getAll(
            0,
            25,
            [
                'q.description LIKE :filename OR q.image = :image',
                [
                    'filename' => '%' . $image->getFilename() . '%',
                    'image' => $image,
                ]
            ]
        );
    }
}
