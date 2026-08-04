<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\Page;
use Inachis\Repository\Content\CategoryRepository;
use Ramsey\Uuid\Uuid;

/**
 * Manager class for applying category to a Page.
 */
class CategoryManager
{
    /**
     * Constructor for CategoryManager.
     */
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * Apply specified categoryId as a {@link Category} to the provided {@link Page}.
     */
    public function apply(Page $page, string $categoryId): void
    {
        $page->removeCategories();
        if (!Uuid::isValid($categoryId)) {
            return;
        }

        $category = $this->categoryRepository->find($categoryId);
        if ($category) {
            $page->addCategory($category);
        }
    }
}
