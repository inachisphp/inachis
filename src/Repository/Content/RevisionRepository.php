<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository\Content;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Entity\User\User;
use Inachis\Repository\AbstractRepository;

/**
 * Repository for revision entities.
 *
 * @extends AbstractRepository<Revision>
 */
class RevisionRepository extends AbstractRepository implements RevisionRepositoryInterface
{
    /**
     * The action type for a deleted revision.
     */
    public const DELETED = 'Deleted';
    /**
     * The action type for a published revision.
     */
    public const PUBLISHED = 'Published';
    /**
     * The action type for an updated revision.
     */
    public const UPDATED = 'Updated';
    /**
     * The action type for a visibility change revision.
     */
    public const VISIBILITY_CHANGE = 'Visibility changed to %s';
    /**
     * The action type for a reverted revision.
     */
    public const REVERTED = 'Reverted to version %s';

    /**
     * RevisionRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    /**
     * Hydrate a new revision from a page.
     *
     * @param Page $page the page to hydrate the revision from
     *
     * @return Revision the hydrated revision
     *
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function hydrateNewRevisionFromPage(Page $page): Revision
    {
        $revision = new Revision();

        return $revision
            ->setPage($page)
            ->setVersionNumber($this->getNextVersionNumberForPage($page))
            ->setTitle($page->getTitle())
            ->setSubTitle($page->getSubTitle())
            ->setContent($page->getContent())
            ->setUser($page->getAuthor())
        ;
    }

    /**
     * Get the next version number for a page.
     *
     * @param Page|null $page the ID of the page
     *
     * @return int the next version number
     *
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNextVersionNumberForPage(?Page $page): int
    {
        return ((int) $this->createQueryBuilder('r')
            ->select('MAX(r.versionNumber) as max_version')
            ->where('r.page = :page')
            ->setParameter('page', $page)
            ->getQuery()
            ->getSingleScalarResult()) + 1;
    }

    /**
     * Get revisions for a specific page.
     *
     * @return list<Revision>
     */
    public function getRevisionsForPage(Page $page)
    {
        /* @var list<Revision> */
        return $this->createQueryBuilder('r')
            ->where('r.page = :pageId')
            ->setParameter('pageId', $page->getId(), 'uuid_binary')
            ->orderBy('r.versionNumber', 'DESC')
            ->setMaxResults(25)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete a page and record the deletion as a revision.
     *
     * @param Page      $page the page to delete
     * @param User|null $user the user performing the deletion
     *
     * @return Revision the recorded revision
     *
     * @throws \Exception
     */
    public function deleteAndRecordByPage(Page $page, ?User $user): Revision
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.page = :page')
            ->setParameter('page', $page)
            ->getQuery()
            ->execute();

        $revision = new Revision();
        $revision
            ->setPage(null)
            ->setTitle($page->getTitle())
            ->setSubTitle($page->getSubTitle())
            ->setUser($user)
            ->setAction(self::DELETED);
        $this->getEntityManager()->persist($revision);
        $this->getEntityManager()->flush();

        return $revision;
    }
}
