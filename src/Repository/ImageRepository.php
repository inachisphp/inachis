<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Image repository
 */
class ImageRepository extends AbstractRepository implements ResourceRepositoryInterface
{
    use DefaultResourceRepository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * Get all images that do not have alt text
     *
     * @param int $limit
     * @param int $offset
     * @return array<Image>
     */
    public function getImagesWithoutAltText(int $limit = 0, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb = $qb
            ->select('i')
            ->where('i.alt IS NULL OR i.alt = :emptyString')
            ->setParameter('emptyString', '');
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        if ($offset > 0) {
            $qb->setFirstResult($offset);
        }
        /** @var array<Image> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Get the number of images that do not have alt text
     *
     * @return int
     */
    public function getImagesWithoutAltTextCount(): int
    {
        $qb = $this->createQueryBuilder('i');
        $qb = $qb
            ->select('COUNT(i)')
            ->where('i.altText IS NULL OR i.altText = :emptyString')
            ->setParameter('emptyString', '');
        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }
}
