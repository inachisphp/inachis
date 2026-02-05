<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Page\Import;

use Inachis\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Mapper for importing pages.
 */
final class PageImportMapper
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Resolve or create category chain from full path.
     * @return Category The last Category in the path.
     */
    public function resolveCategoryPath(string $fullPath, bool $createMissing = false): Category
    {
        $parts = array_map('trim', explode('/', $fullPath));
        $parent = null;

        foreach ($parts as $title) {
            $category = $this->entityManager->getRepository(Category::class)
                ->findOneBy(['title' => $title, 'parent' => $parent]);

            if (!$category) {
                if (!$createMissing) {
                    throw new \RuntimeException("Category not found: $fullPath");
                }
                $category = new Category($title);
                $category->setParent($parent);
                $this->entityManager->persist($category);
            }

            $parent = $category;
        }

        return $parent;
    }

    /**
     * Resolve or create tag from title.
     * @return Tag The tag.
     */
    public function resolveTag(string $title, bool $createMissing = false): Tag
    {
        $tag = $this->entityManager->getRepository(Tag::class)
            ->findOneBy(['title' => $title]);

        if (!$tag) {
            if (!$createMissing) {
                throw new \RuntimeException("Tag not found: $title");
            }
            $tag = new Tag($title);
            $this->entityManager->persist($tag);
        }

        return $tag;
    }
}
