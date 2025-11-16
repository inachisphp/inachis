<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use App\Entity\Page;
use App\Entity\Url;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class UrlRepository extends AbstractRepository
{
    /**
     * The maximum number of items to show in the admin interface
     */
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
     * @param Url $url
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(Url $url): void
    {
        $this->getEntityManager()->remove($url);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Page $page
     *
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
     * @param $filters
     * @param int $offset
     * @param int $limit
     * @param string $sort
     * @return Paginator
     */
    public function getFiltered(
        $filters,
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
        $sort = match ($sort) {
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
        return $this->getAll(
            $offset,
            $limit,
            $where,
            $sort,
            [],
            ['q.content', 'p']
        );
    }
}
