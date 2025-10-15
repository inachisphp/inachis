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
use Doctrine\Persistence\ManagerRegistry;

class UrlRepository extends AbstractRepository
{
    /**
     * The maximum number of items to show in the admin interface
     */
    const MAX_ITEMS_TO_SHOW_ADMIN = 20;

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
            ->setParameters([
                'url' => $url . '%',
                'id' => $id,
            ])
            ->setMaxResults(1);
        return $qb
            ->getQuery()
            ->execute();
    }
}
