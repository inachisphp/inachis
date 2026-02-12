<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Tag;

final class TagImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Find a tag by title, or optionally create it.
     *
     * @param string $title
     * @param bool $createIfMissing
     * @return Tag|null
     */
    public function findOrCreateByTitle(string $title, bool $createIfMissing = false): ?Tag
    {
        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['title' => $title]);

        if (!$tag && $createIfMissing) {
            $tag = new Tag($title);
            $this->entityManager->persist($tag);
        }

        return $tag;
    }
}