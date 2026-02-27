<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Category;

/**
 * Service for importing categories.
 */
final class CategoryImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Find a category by its full path, or optionally create missing categories.
     *
     * @param string $fullPath e.g. "Trips/Europe/France"
     * @param bool $createIfMissing
     * @return Category|null
     */
    public function findOrCreateByPath(string $fullPath, bool $createIfMissing = false): ?Category
    {
        $segments = array_filter(explode('/', $fullPath));
        if (empty($segments)) {
            return null;
        }

        $parent = null;

        foreach ($segments as $title) {
            $category = $this->entityManager->getRepository(Category::class)
                ->findOneBy(['title' => $title, 'parent' => $parent]);

            if (!$category && $createIfMissing) {
                $category = new Category($title);
                $category->setParent($parent);
                $this->entityManager->persist($category);
            }

            if (!$category) {
                return null;
            }

            $parent = $category;
        }

        return $parent;
    }
}