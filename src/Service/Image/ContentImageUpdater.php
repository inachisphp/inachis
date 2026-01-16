<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Image;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Revision;
use Inachis\Repository\RevisionRepository;

class ContentImageUpdater
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function updateEntity($entity, string $field, array $changes, bool $hasRevisions = false): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);

        $updated = str_replace($changes['source'], $changes['destination'], $entity->$getter());
        $entity->$setter($updated);
        $entity->setModDate(new DateTime());

        if ($hasRevisions) {
            $revision = $this->em->getRepository(Revision::class)->hydrateNewRevisionFromPage($entity);
            $revision->setAction(RevisionRepository::UPDATED);
            $this->em->persist($revision);
        }

        $this->em->persist($entity);
        $this->em->flush();
    }
}
