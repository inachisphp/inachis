<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Tag;

final class TagImportService
{
    /** @var array<string, Tag> */
    private array $cache = [];

    /** @var array<string, bool> */
    private array $createdInSession = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Find a tag by title, or optionally create it.
     */
    public function findOrCreateByTitle(string $title, bool $createIfMissing = false): ?Tag
    {
        $normalized = mb_strtolower(trim($title));
        if ('' === $normalized) {
            return null;
        }

        if (isset($this->cache[$normalized])) {
            return $this->cache[$normalized];
        }

        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['title' => $normalized]);

        if (!$tag && $createIfMissing) {
            $tag = new Tag($normalized);
            $this->entityManager->persist($tag);
            $this->cache[$normalized] = $tag;
            $this->createdInSession[$normalized] = true;
        } elseif ($tag) {
            $this->cache[$normalized] = $tag;
        }

        return $tag;
    }

    /**
     * Returns true if the tag was newly created during the current import session.
     */
    public function wasCreated(string $title): bool
    {
        $normalized = mb_strtolower(trim($title));

        return !empty($this->createdInSession[$normalized]);
    }
}
