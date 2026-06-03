<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Inachis\Entity\Content\{Page,Revision};
use Inachis\Entity\User\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Ramsey\Uuid\UuidInterface;
use DateTimeImmutable;

/**
 * Repository for revision entities
 */
class RevisionRepository extends AbstractRepository implements RevisionRepositoryInterface
{
    /**
     * The action type for a deleted revision
     */
    public const DELETED = 'Deleted';
    /**
     * The action type for a published revision
     */
    public const PUBLISHED = 'Published';
    /**
     * The action type for an updated revision
     */
    public const UPDATED = 'Updated';
    /**
     * The action type for a visibility change revision
     */
    public const VISIBILITY_CHANGE = 'Visibility changed to %s';
    /**
     * The action type for a reverted revision
     */
    public const REVERTED = 'Reverted to version %s';

    /**
     * RevisionRepository constructor
     * 
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    /**
     * Hydrate a new revision from a page
     * 
     * @param Page $page The page to hydrate the revision from.
     * @return Revision The hydrated revision.
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function hydrateNewRevisionFromPage(Page $page): Revision
    {
        $revision = new Revision();
        return $revision
            ->setPageId($page->getId())
            ->setVersionNumber($this->getNextVersionNumberForPageId($page->getId()))
            ->setTitle($page->getTitle())
            ->setSubTitle($page->getSubTitle())
            ->setContent($page->getContent())
            ->setUser($page->getAuthor())
            ->setModDate($page->getModDate());
    }

    /**
     * Get the next version number for a page
     * 
     * @param UuidInterface $pageId The ID of the page.
     * @return int The next version number.
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNextVersionNumberForPageId(UuidInterface $pageId): int
    {
        return ((int) $this->createQueryBuilder('r')
            ->select('MAX(r.versionNumber) as max_version')
            ->where('r.page_id = :pageId')
            ->setParameter('pageId', $pageId)
            ->getQuery()
            ->getSingleScalarResult()) + 1;
    }

    /**
     * Delete a page and record the deletion as a revision
     * 
     * @param Page $page The page to delete.
     * @param User|null $user The user performing the deletion.
     * @return Revision The recorded revision.
     * @throws \Exception
     */
    public function deleteAndRecordByPage(Page $page, ?User $user): Revision
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.page_id = :pageId')
            ->setParameter('pageId', $page->getId())
            ->getQuery()
            ->execute();

        $revision = new Revision();
        $revision
            ->setPageId($page->getId())
            ->setTitle($page->getTitle())
            ->setSubTitle($page->getSubTitle())
            ->setUser($user)
            ->setModDate(new DateTimeImmutable())
            ->setAction(self::DELETED);
        $this->getEntityManager()->persist($revision);
        $this->getEntityManager()->flush();

        return $revision;
    }
}
