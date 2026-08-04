<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Media\Image;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\AbstractRepository;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractRepository<Series>
 */
class SeriesRepository extends AbstractRepository implements SeriesRepositoryInterface
{
    /**
     * Constructor for SeriesRepository.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    /**
     * Removes a Series entity from the database.
     */
    public function remove(Series $series): void
    {
        $this->getEntityManager()->remove($series);
    }

    /**
     * Get a paginator of Series entities filtered by the given IDs.
     *
     * @param list<string> $ids
     *
     * @return Paginator<Series>
     */
    public function getFilteredIds(array $ids): Paginator
    {
        $binaryIds = array_map(
            fn ($id) => $id instanceof \Ramsey\Uuid\UuidInterface
                ? $id->getBytes()
                : Uuid::fromString($id)->getBytes(),
            $ids,
        );

        return $this->getAll(
            0,
            0,
            [
                'q.id IN (:ids)',
                [
                    'ids' => [
                        'value' => $binaryIds,
                    ],
                ],
            ],
        );
    }

    /**
     * Get the Series associated with a given Page.
     * This method returns the Series that contains the specified Page as one of its items.
     * If multiple Series contain the same Page, it will return one of them (the first found).
     *
     * @param Page $page the Page for which to find the associated Series
     *
     * @return Series|null the Series associated with the given Page, or null if no such Series exists
     *
     * @throws NonUniqueResultException
     */
    public function getSeriesByPost(Page $page): ?Series
    {
        /* @var Series|null */
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
     * This method returns the Series that contains the specified Page as one of its items, and is published
     * (visible = 1).
     * If multiple Series contain the same Page, it will return one of them (the first found).
     *
     * @param Page $page the Page for which to find the associated Series
     *
     * @return Series|null The published Series associated with the given Page, or null if no
     *
     * @throws NonUniqueResultException
     */
    public function getPublishedSeriesByPost(Page $page)
    {
        $qb = $this->createQueryBuilder('s');

        /* @var Series|null */
        return $qb
            ->select('s', 'i')
            ->join('s.items', 'i')
            ->where(':page MEMBER OF s.items')
            ->andWhere('i.status = :status')
            ->andWhere('s.visible = :visible')
            ->setParameter('page', $page)
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('visible', true)
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
     * @param string $year the year to match against the lastDate field (format: 'YYYY')
     * @param string $url  the URL to match against the url field of the Series
     *
     * @return Series|null the public Series that matches the given year and URL, or null if no such Series exists
     */
    public function getPublicSeriesByYearAndUrl($year, $url): ?Series
    {
        $qb = $this->createQueryBuilder('s');

        /* @var Series|null */
        return $qb
            ->select('s')
            ->where('s.lastDate >= :start')
            ->andWhere('s.lastDate < :end')
            ->andWhere($qb->expr()->eq('s.url', ':url'))
            ->andWhere('s.visible = :visible')
            ->setParameter('start', $year.'-01-01')
            ->setParameter('end', $year.'-12-31')
            ->setParameter('url', $url)
            ->setParameter('visible', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get a paginator of Series entities filtered by the given criteria.
     *
     * @param array{keyword?:string,visible?:string} $filters
     *
     * @return Paginator<Series>
     */
    public function getFiltered(array $filters, int $limit, int $offset, string $sort = ''): Paginator
    {
        $where = [
            '1=1',
            $filters,
        ];
        if (!empty($filters['keyword'])) {
            $where[0] .= ' AND (q.title LIKE :keyword OR q.subTitle LIKE :keyword OR q.description LIKE :keyword )';
            $where[1]['keyword'] = '%'.$filters['keyword'].'%';
        }
        if (isset($filters['visibility'])) {
            $where[0] .= ' AND q.visible = :visibility';
        }
        $sort = match ($sort) {
            'title desc' => [
                ['q.title', 'DESC'],
                ['q.subTitle', 'DESC'],
            ],
            'updatedAt asc' => [['q.updatedAt', 'ASC']],
            'updatedAt desc' => [['q.updatedAt', 'DESC']],
            'lastDate asc' => [['q.lastDate', 'ASC']],
            'lastDate desc' => [
                ['CASE WHEN q.lastDate IS NULL THEN 1 ELSE 0 END', 'DESC'],
                ['q.lastDate', 'DESC'],
            ],
            default => [
                ['q.title', 'ASC'],
                ['q.subTitle', 'ASC'],
            ],
        };

        return $this->getAll(
            $limit,
            $offset,
            $where,
            $sort,
        );
    }

    /**
     * Get a paginator of Series entities that are associated with a given Image.
     * This method retrieves Series entities where the specified Image is either directly associated with the Series (
     * i.e., the Image is set as the Series' image) or indirectly associated through the Series' description (i.e.,
     * the Image's filename is mentioned in the Series' description).
     *
     * @return Paginator<Series>
     */
    public function getSeriesUsingImage(Image $image): Paginator
    {
        return $this->getAll(
            25,
            0,
            [
                'q.description LIKE :filename OR q.image = :image',
                [
                    'filename' => '%'.$image->getFilename().'%',
                    'image' => $image->getId()?->toString() ?? '',
                ],
            ],
        );
    }

    /**
     * Return a count of public series.
     */
    public function countPublicSeries(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.visible = :visible')
            ->setParameter('visible', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Return a batch of public series, ordered by lastDate desc, with pagination.
     *
     * @return array<Series>
     */
    public function findPublicSeriesBatch(
        int $limit,
        int $offset,
    ): array {
        /* @var array<Series> */
        return $this->createQueryBuilder('s')
            ->where('s.visible = :visible')
            ->setParameter('visible', true)
            ->orderBy('s.lastDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns an array of {@link Series} that are recent drafts
     * ordered by the date of the attached content.
     *
     * @return array<Series>
     */
    public function findRecentDrafts(int $limit = 5): array
    {
        /* @var array<Series> */
        return $this->createQueryBuilder('s')
            ->where('s.visible = :visible')
            ->setParameter('visible', false)
            ->orderBy('s.firstDate', 'DESC')
            ->addOrderBy('s.lastDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns an array of recently published {@link Series}.
     *
     * @return array<Series>
     */
    public function findRecentPublished(int $limit = 5): array
    {
        /* @var array<Series> */
        return $this->createQueryBuilder('s')
            ->where('s.visible = :visible')
            ->setParameter('visible', true)
            ->orderBy('s.firstDate', 'DESC')
            ->addOrderBy('s.lastDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
