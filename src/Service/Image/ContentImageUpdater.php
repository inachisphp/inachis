<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Image;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Revision;
use Inachis\Repository\RevisionRepository;

/**
 * Handles updating of content fields containing image URLs, and optionally creates a new revision for the change.
 * The updateEntity method takes an entity, the field to update, an array of changes (with 'source' and 
 * 'destination' keys), and a flag indicating whether to create a revision. It updates the content by replacing 
 * the source URL with the destination URL, sets the modification date, and persists the changes to the database. 
 * If revisions are enabled, it also creates a new revision entry for the change.
 */
class ContentImageUpdater
{
    /**
     * Constructor for ContentImageUpdater.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Updates the target field of the given entity by replacing the source URL with the destination URL, and 
     * optionally creates a new revision.
     *
     * @param mixed $entity
     * @param string $field
     * @param array $changes
     * @param boolean $hasRevisions
     */
    public function updateEntity(mixed $entity, string $field, array $changes, bool $hasRevisions = false): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);

        $updated = str_replace($changes['source'], $changes['destination'], $entity->$getter());
        $entity->$setter($updated);
        $entity->setModDate(new DateTimeImmutable());

        if ($hasRevisions) {
            $revision = $this->entityManager->getRepository(Revision::class)->hydrateNewRevisionFromPage($entity);
            $revision->setAction(RevisionRepository::UPDATED);
            $this->entityManager->persist($revision);
        }

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
