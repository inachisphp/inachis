<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository\Content;

use Inachis\Entity\Content\Tag;
use Inachis\Repository\AbstractRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Repository for {@link Tag} entities
 * 
 * @extends AbstractRepository<Tag>
 */
class TagRepository extends AbstractRepository
{
    /**
     * Creates a new instance of the TagRepository
     *
     * @param ManagerRegistry $registry The registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Finds tags by title
     *
     * @param string $title The title to search for
     * @return Paginator<Tag> The paginator
     */
    public function findByTitleLike(string $title): Paginator
    {
        return $this->getAll(
            0,
            25,
            [
                'q.title LIKE :title',
                [
                    'title' => '%' . $title . '%',
                ],
            ],
            'q.title'
        );
    }

    /**
     * Normalises a tag title.
     *
     * @param string $value
     * @return string
     * @throws \InvalidArgumentException if the normalised value is empty
     */
    private function normalise(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value);
        $value = (string) preg_replace('/\s+/', ' ', $value);

        if ($value === '') {
            throw new \InvalidArgumentException('Tag cannot be empty');
        }

        return $value;
    }

    /**
     * Gets a tag by its title, or creates it if it doesn't exist.
     *
     * @param string $title
     * @return Tag
     */
    public function getOrCreate(string $title): Tag
    {
        $em = $this->getEntityManager();
        $normalised = $this->normalise($title);

        // Fast path
        $existing = $this->findOneBy(['title' => $normalised]);
        if ($existing !== null) {
            return $existing;
        }

        // Create
        $tag = new Tag($normalised);
        $em->persist($tag);

        try {
            $em->flush();
            return $tag;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            // Another request created it
            $em->detach($tag);

            $existing = $this->findOneBy(['title' => $normalised]);
            if ($existing !== null) {
                return $existing;
            }

            throw new \RuntimeException(sprintf(
                'Failed to create or retrieve tag "%s"',
                $normalised
            ));
        }
    }

    /**
     * Gets all tags with usage count.
     *
     * @param int $offset
     * @param int $limit
     * @return list<array{0:Tag, usageCount:int}>
     */
    public function findAllWithUsageCount(int $offset = 0, int $limit = 0): array
    {
        $qb = $this->getEntityManager()->createQuery(
            'SELECT t, COUNT(p.id) AS usageCount
             FROM Inachis\Entity\Content\Tag t
             LEFT JOIN Inachis\Entity\Content\Page p WITH t MEMBER OF p.tags
             GROUP BY t.id
             ORDER BY t.title ASC'
        );

        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        if ($limit > 0) {
            $qb = $qb->setMaxResults($limit);
        }

        /** @var list<array{0:Tag, usageCount:int}> */
        return $qb->getResult();
    }

    /**
     * retund a count of tags
     *
     * @return int
     */
    public function countTags(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Return a batch of tags, ordered by title, with pagination.
     *
     * @param int $offset
     * @param int $limit
     * @return array<int,Tag>
     */
    public function findBatch(
        int $offset,
        int $limit
    ): array {
        /** @var array<int,Tag> $result */
        $result = $this->createQueryBuilder('t')
            ->orderBy('t.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        return $result;
    }
}
