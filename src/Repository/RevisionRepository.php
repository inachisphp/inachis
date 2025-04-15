<?php

namespace App\Repository;

use App\Entity\Page;
use App\Entity\Revision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use \Doctrine\ORM\NonUniqueResultException;

/**
 * @method Revision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Revision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Revision[]    findAll()
 * @method Revision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RevisionRepository extends AbstractRepository
{
    const DELETED = 'Deleted';
    const PUBLISHED = 'Published';
    const UPDATED = 'Updated';
    const VISIBILITY_CHANGE = 'Visibility changed to %s';
    const REVERTED = 'Reverted to version %s';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Revision::class);
    }

    /**
     * @param Page $page
     * @return Revision
     * @throws NonUniqueResultException
     * @throws \Exception
     */
    public function hydrateNewRevisionFromPage(Page $page)
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
     * @param string $pageId
     * @return int
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNextVersionNumberForPageId(string $pageId)
    {
        return ((int) $this->createQueryBuilder('r')
            ->select('MAX(r.versionNumber) as max_version')
            ->where('r.page_id = :pageId')
            ->setParameter('pageId', $pageId)
            ->getQuery()
            ->getSingleScalarResult()) + 1;
    }

    /**
     * @param Page $page
     * @return Revision
     * @throws \Exception
     */
    public function deleteAndRecordByPage(Page $page): Revision
    {
        $this->createQueryBuilder('r')
            ->delete()
            ->where('r.page_id = :pageId')
            ->setParameter('pageId', $page->getId());
        $revision = new Revision();
        $revision
            ->setPageId($page->getId())
            ->setTitle($page->getTitle())
            ->setSubTitle($page->getSubTitle())
            ->setUser()
            ->setModDate(new \DateTime())
            ->setAction(self::DELETED);
        return $revision;
    }
}
