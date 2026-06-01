<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Page;
use Inachis\Entity\Url;
use Inachis\Enum\EditorialStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class UrlRepository extends AbstractRepository
{
    /** @var int The maximum number of items to show in the admin interface */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 20;

    /**
     * UrlRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Url::class);
    }

    /**
     * Remove a Url entity from the database.
     *
     * @param Url $url
     */
    public function remove(Url $url): void
    {
        $this->getEntityManager()->remove($url);
        $this->getEntityManager()->flush();
    }

    /**
     * This method retrieves the default URL associated with the specified Page.
     *
     * @param Page $page
     * @return mixed
     */
    public function getDefaultUrl(Page $page): mixed
    {
        return $this->findOneBy(
            [
                'content' => $page,
                'default' => true,
            ]
        );
    }

    /**
     * Find URLs that are similar to the given URL, excluding a specific ID.
     * This is useful for ensuring URL uniqueness when updating or creating new URLs.
     *
     * @param string $url
     * @param string $id
     * @return float|int|mixed|string
     */
    public function findSimilarUrlsExcludingId(string $url, string $id)
    {
        $qb = $this->createQueryBuilder('u');
        $qb = $qb
            ->select('u.link')
            ->where(
                $qb->expr()->andX(
                    'u.link LIKE  :url',
                    $qb->expr()->not($qb->expr()->eq('u.content', ':id'))
                )
            )
            ->orderBy('u.link', 'DESC')
            ->setParameter('url', $url . '%')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * Determine the order by clause based on the input parameter.
     * This method maps specific sort options to corresponding database fields and sort directions.
     *
     * @param string $orderBy
     * @return array<array{0: string, 1: string}>
     */
    protected function determineOrderBy(string $orderBy): array
    {
        return match ($orderBy) {
            'contentDate desc' => [
                [ 'substring(q.link, 1, 10)', 'desc' ],
                [ 'q.default', 'desc' ],
                [ 'q.createDate', 'desc' ],
            ],
            'link asc' => [['q.link', 'ASC']],
            'link desc' => [['q.link', 'DESC']],
            'content asc' => [['p.title', 'ASC']],
            'content desc' => [['p.title', 'DESC']],
            default => [
                [ 'substring(q.link, 1, 10)', 'asc' ],
                [ 'q.default', 'desc' ],
                [ 'q.createDate', 'desc' ],
            ],
        };
    }

    /**
     * Find a URL by its link, with an optional parameter to exclude a specific ID.
     *
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator
     */
    public function getFiltered(
        array $filters,
        int $offset,
        int $limit,
        string $sort = 'postDate desc'
    ): Paginator {
        $where = [];
        if (!empty($filters['keyword'])) {
            $where = [
                '(p.title LIKE :keyword OR q.link LIKE :keyword)',
                [
                    'keyword' => '%' . $filters['keyword'] . '%',
                ]
            ];
        }
        return $this->getAll(
            $offset,
            $limit,
            $where,
            $this->determineOrderBy($sort),
            [],
            ['q.content', 'p']
        );
    }

    /**
     * Count the number of URLs that will be used in the sitemap,
     * based on specific criteria such as visibility, status, and indexing rules.
     *
     * @return integer
     */
    public function countSitemapUrls(): int
    {
        $now = new \DateTimeImmutable();

        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->innerJoin('u.content', 'p')
            ->andWhere('u.default = :default')
            ->andWhere('p.visibility = :visible')
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
     * @param integer $offset
     * @param integer $limit
     * @return array<Url>
     */
    public function findSitemapUrlsBatch(
        int $offset,
        int $limit
    ): array {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('u')
            ->innerJoin('u.content', 'p')
            ->addSelect('p')
            ->andWhere('u.default = :default')
            ->andWhere('p.visibility = :visible')
            ->andWhere('p.status = :status')
            ->andWhere('p.postDate <= :now')
            ->andWhere('(p.expireDate IS NULL OR p.expireDate > :now)')
            ->andWhere('p.noindex = false')
            ->setParameter('default', true)
            ->setParameter('visible', true)
            ->setParameter('status', EditorialStatus::PUBLISHED)
            ->setParameter('now', $now)
            ->orderBy('p.postDate', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
