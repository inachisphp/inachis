<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Page;
use Inachis\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TagRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @param $title
     * @return Paginator
     */
    public function findByTitleLike($title): Paginator
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
     */
    private function normalize(string $value): string
    {
        $value = trim($value);
        $value = mb_strtolower($value);
        $value = preg_replace('/\s+/', ' ', $value);

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
        $normalized = $this->normalize($title);

        // Fast path
        $existing = $this->findOneBy(['title' => $normalized]);
        if ($existing !== null) {
            return $existing;
        }

        // Create
        $tag = new Tag($normalized);
        $em->persist($tag);

        try {
            $em->flush();
            return $tag;
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            // Another request created it
            $em->detach($tag);

            $existing = $this->findOneBy(['title' => $normalized]);
            if ($existing !== null) {
                return $existing;
            }

            throw new \RuntimeException(sprintf(
                'Failed to create or retrieve tag "%s"',
                $normalized
            ));
        }
    }

    /**
     * Gets all tags with usage count.

     * @return array<array{tag: Tag, usageCount: int}>
     */
    public function findAllWithUsageCount(): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT t, COUNT(p.id) AS usageCount
             FROM Inachis\Entity\Tag t
             LEFT JOIN Inachis\Entity\Page p WITH t MEMBER OF p.tags
             GROUP BY t.id
             ORDER BY t.title ASC'
        )->getResult();
    }
}
