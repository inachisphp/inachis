<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Inachis\Entity\Content\{Page,Series};
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\AbstractRepository;
use Inachis\Repository\Content\SeriesRepositoryInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractRepository<Series>
 */
class SeriesRepository extends AbstractRepository implements SeriesRepositoryInterface
{
    /**
     * Constructor for SeriesRepository
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * Removes a Series entity from the database.
     *
     * @param Series $series
     */
    public function remove(Series $series): void
    {
        $this->getEntityManager()->remove($series);
        $this->getEntityManager()->flush();
    }

    /**
     * Get a paginator of Series entities filtered by the given IDs.
     *
     * @param array<string> $ids
     * @return Paginator<Series>
     */
    public function getFilteredIds(array $ids): Paginator
    {
        return $this->getAll(
            0,
            0,
            [
                'q.id IN (:ids)',
                [
                    'ids' => $ids,
                ]
            ]
        );
    }

    /**
     * Get the Series associated with a given Page.
     * This method returns the Series that contains the specified Page as one of its items.
     * If multiple Series contain the same Page, it will return one of them (the first found).
     *
     * @param Page $page The Page for which to find the associated Series.
     * @return Series|null The Series associated with the given Page, or null if no such Series exists.
     * @throws NonUniqueResultException
     */
    public function getSeriesByPost(Page $page): ?Series
    {
        /** @var Series|null */
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.items', 'Series_pages')
            ->where('Series_pages.id = :pageId')
            ->setParameter('pageId', $page->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get the published Series associated with a given Page.
     * This method returns the Series that contains the specified Page as one of its items, and is published (visibility = public).
     * If multiple Series contain the same Page, it will return one of them (the first found).
     *
     * @param Page $page The Page for which to find the associated Series.
     * @return Series|null The published Series associated with the given Page, or null if no
     * @throws NonUniqueResultException
     */
    public function getPublishedSeriesByPost(Page $page)
    {
        $qb = $this->createQueryBuilder('s');
        /** @var Series|null */
        return $qb
            ->select('s', 'i')
            ->join('s.items', 'i')
            ->where(':page MEMBER OF s.items')
            ->andWhere('i.status = :status')
            ->andWhere('s.visibility = :visibility')
            ->setParameter('page', $page)
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('visibility', Series::PUBLIC)
            ->orderBy('i.postDate', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get a public Series by year and URL.
     * This method retrieves a Series that is publicly visible and matches the specified year and URL.
     * The year is matched against the lastDate field of the Series, and the URL is matched against the url field.
     * If no such Series exists, it returns null.
     *
     * @param string $year The year to match against the lastDate field (format: 'YYYY').
     * @param string $url The URL to match against the url field of the Series.
     * @return Series|null The public Series that matches the given year and URL, or null if no such Series exists.
     */
    public function getPublicSeriesByYearAndUrl($year, $url): ?Series
    {
        $qb = $this->createQueryBuilder('s');
        /** @var Series|null */
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
     * Get a paginator of Series entities filtered by the given criteria.
     *
     * @param array{keyword?:string,visibility?:string} $filters
     * @param int $offset
     * @param int $limit
     * @return Paginator<Series>
     */
    public function getFiltered(array $filters, int $offset, int $limit, string $sort = ''): Paginator
    {
        $where = [
            '1=1',
            $filters,
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.description LIKE :keyword )';
            $where[1]['keyword'] = '%' . $filters['keyword'] . '%';
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
     * Get a paginator of Series entities that are associated with a given Image.
     * This method retrieves Series entities where the specified Image is either directly associated with the Series (
     * i.e., the Image is set as the Series' image) or indirectly associated through the Series' description (i.e.,
     * the Image's filename is mentioned in the Series' description).
     *
     * @param Image $image
     * @return Paginator<Series>
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

    /**
     * Return a count of public series
     *
     * @return int
     */
    public function countPublicSeries(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', Series::PUBLIC)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Return a batch of public series, ordered by lastDate desc, with pagination.
     *
     * @param int $offset
     * @param int $limit
     * @return array<Series>
     */
    public function findPublicSeriesBatch(
        int $offset,
        int $limit
    ): array {
        /** @var array<Series> */
        return $this->createQueryBuilder('s')
            ->where('s.visibility = :visibility')
            ->setParameter('visibility', Series::PUBLIC)
            ->orderBy('s.lastDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
