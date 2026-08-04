<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Url;
use Inachis\Enum\EditorialStatus;
use Inachis\Repository\AbstractRepository;

/**
 * Repository for managing {@link Url} entities.
 *
 * @extends AbstractRepository<Url>
 */
class UrlRepository extends AbstractRepository
{
    /** @var int The maximum number of items to show in the admin interface */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 20;

    /**
     * UrlRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Url::class);
    }

    /**
     * Remove a Url entity from the database.
     */
    public function remove(Url $url): void
    {
        $this->getEntityManager()->remove($url);
        $this->getEntityManager()->flush();
    }

    /**
     * This method retrieves the default URL associated with the specified Page.
     */
    public function getDefaultUrl(Page $page): mixed
    {
        return $this->findOneBy(
            [
                'content' => $page,
                'default' => true,
            ],
        );
    }

    /**
     * Find URLs that are similar to the given URL, excluding a specific ID.
     * This is useful for ensuring URL uniqueness when updating or creating new URLs.
     *
     * @return list{0?: array{link: string}}
     */
    public function findSimilarUrlsExcludingId(string $url, string $id)
    {
        $qb = $this->createQueryBuilder('u');

        /* @var list{0?: array{link: string}} */
        return $qb
            ->select('u.link')
            ->where(
                $qb->expr()->andX(
                    'u.link LIKE  :url',
                    $qb->expr()->not($qb->expr()->eq('u.content', ':id')),
                ),
            )
            ->orderBy('u.link', 'DESC')
            ->setParameter('url', $url.'%')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * Determine the order by clause based on the input parameter.
     * This method maps specific sort options to corresponding database fields and sort directions.
     *
     * @return list<array{0: string, 1: string}>
     */
    protected function determineOrderBy(string $orderBy): array
    {
        return match ($orderBy) {
            'contentDate desc' => [
                ['substring(q.link, 1, 10)', 'desc'],
                ['q.default', 'desc'],
                ['q.createdAt', 'desc'],
            ],
            'link asc' => [['q.link', 'ASC']],
            'link desc' => [['q.link', 'DESC']],
            'content asc' => [['p.title', 'ASC']],
            'content desc' => [['p.title', 'DESC']],
            default => [
                ['substring(q.link, 1, 10)', 'asc'],
                ['q.default', 'desc'],
                ['q.createdAt', 'desc'],
            ],
        };
    }

    /**
     * Find a URL by its link, with an optional parameter to exclude a specific ID.
     *
     * @param array{keyword?:string} $filters
     *
     * @return Paginator<Url>
     */
    public function getFiltered(
        array $filters,
        int $limit,
        int $offset,
        string $sort = 'postDate desc',
    ): Paginator {
        $where = [];
        if (!empty($filters['keyword'])) {
            $where = [
                '(p.title LIKE :keyword OR q.link LIKE :keyword)',
                [
                    'keyword' => '%'.$filters['keyword'].'%',
                ],
            ];
        }

        return $this->getAll(
            $limit,
            $offset,
            $where,
            $this->determineOrderBy($sort),
            [],
            [['join', 'q.content', 'p']],
        );
    }

    /**
     * Count the number of URLs that will be used in the sitemap,
     * based on specific criteria such as visibility, status, and indexing rules.
     */
    public function countSitemapUrls(): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->innerJoin('u.content', 'p')
            ->andWhere('u.default = :default')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate > :now)')
            ->andWhere('p.noindex = false')
            ->setParameter('default', true)
            ->setParameter('visible', true)
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find a batch of URLs for the sitemap, based on specific criteria.
     * This method retrieves a subset of URLs that meet the conditions for inclusion in the sitemap,
     * such as being the default URL for a page, having the page be visible and published, and not
     * being marked as noindex.
     *
     * @return array<Url>
     */
    public function findSitemapUrlsBatch(
        int $limit,
        int $offset,
    ): array {
        $now = new \DateTimeImmutable();

        /** @var array<Url> $results */
        $results = $this->createQueryBuilder('u')
            ->innerJoin('u.content', 'p')
            ->addSelect('p')
            ->andWhere('u.default = :default')
            ->andWhere('p.visible = :visible')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate > :now)')
            ->andWhere('p.noindex = false')
            ->setParameter('default', true)
            ->setParameter('visible', true)
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->orderBy('p.postDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $results;
    }
}
