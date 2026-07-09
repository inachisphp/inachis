<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Media;

use Inachis\Entity\Media\Image;
use Inachis\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Image repository
 * 
 * @extends AbstractRepository<Image>
 * @implements ResourceRepositoryInterface<Image>
 */
class ImageRepository extends AbstractRepository implements ResourceRepositoryInterface
{
    /** @use DefaultResourceRepository<Image> */
    use DefaultResourceRepository;
    
    /** @var int */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 25;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(
        private CacheInterface $cache,
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Image::class);
    }

    /**
     * Get all images that do not have alt text
     *
     * @param int $limit
     * @param int $offset
     * @return Paginator<Image>
     */
    public function getImagesWithoutAltText(int $limit = 0, int $offset = 0): Paginator
    {
        return $this->getAll(
            limit: $limit,
            offset: $offset,
            where: [
                'q.altText IS NULL OR q.altText = :emptyString',
                [
                    'emptyString' => ''
                ]
            ],
            order: [
                ['q.id', 'ASC']
            ]
        );
    }

    /**
     * Get the number of images that do not have alt text
     *
     * @return int
     */
    public function getImagesWithoutAltTextCount(): int
    {
        return $this->cache->get('images_without_alt_text_count', function (ItemInterface $item) {
            $item->expiresAfter(7200);

            $qb = $this->createQueryBuilder('i');

            return (int) $qb->select('COUNT(i)')
                ->where('i.altText IS NULL OR i.altText = :emptyString')
                ->setParameter('emptyString', '')
                ->getQuery()
                ->getSingleScalarResult();
        });
    }

    /**
     * Get a list of the IDs for Image not used in {@link Page} or
     * {@link Series} objects
     *
     * @return list<string>
     */
    private function getUnusedResourceIds(): array
    {
        $sql = '
            SELECT i.id
            FROM image i
            WHERE NOT EXISTS (
                SELECT 1
                FROM page p
                WHERE p.image_id = i.id
            )
            AND NOT EXISTS (
                SELECT 1
                FROM series s
                WHERE s.image_id = i.id
            )
            AND NOT EXISTS (
                SELECT 1
                FROM page p2
                WHERE p2.content LIKE CONCAT("%/imgs/", i.filename, "%")
            )
            AND NOT EXISTS (
                SELECT 1
                FROM series s2
                WHERE s2.description LIKE CONCAT("%/imgs/", i.filename, "%")
            )
        ';

        return array_column(
            $this->getEntityManager()
                ->getConnection()
                ->executeQuery($sql)
                ->fetchAllAssociative(),
            'id'
        );
    }
}
