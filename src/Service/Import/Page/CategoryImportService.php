<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Category;

/**
 * Service for importing categories.
 */
final class CategoryImportService
{
    /** @var array<string, Category> */
    private array $cache = [];

    /** @var array<string, bool> */
    private array $createdInSession = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Find a category by its full path, or optionally create missing categories.
     *
     * @param string $fullPath e.g. "Trips/Europe/France"
     */
    public function findOrCreateByPath(string $fullPath, bool $createIfMissing = false): ?Category
    {
        $segments = array_filter(array_map('trim', explode('/', $fullPath)));
        if (empty($segments)) {
            return null;
        }

        $cleanPath = implode('/', $segments);
        if (isset($this->cache[$cleanPath])) {
            return $this->cache[$cleanPath];
        }

        $parent = null;
        $currentPath = '';

        foreach ($segments as $title) {
            $currentPath = '' === $currentPath ? $title : $currentPath.'/'.$title;

            if (isset($this->cache[$currentPath])) {
                $category = $this->cache[$currentPath];
            } else {
                $category = $this->entityManager->getRepository(Category::class)
                    ->findOneBy(['title' => $title, 'parent' => $parent]);

                if (!$category && $createIfMissing) {
                    $category = new Category($title);
                    $category->setParent($parent);
                    $this->entityManager->persist($category);
                    $this->createdInSession[$currentPath] = true;
                }

                if ($category) {
                    $this->cache[$currentPath] = $category;
                }
            }

            if (!$category) {
                return null;
            }

            $parent = $category;
        }

        return $parent;
    }

    /**
     * Returns true if the category path was newly created during the current import session.
     */
    public function wasCreated(string $fullPath): bool
    {
        $segments = array_filter(array_map('trim', explode('/', $fullPath)));
        $cleanPath = implode('/', $segments);

        return !empty($this->createdInSession[$cleanPath]);
    }
}
