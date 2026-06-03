<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export\Category;

use Inachis\Entity\Content\Category;
use Inachis\Repository\CategoryRepository;
use Inachis\Service\Export\AbstractExportService;
use Inachis\Service\Export\Category\CategoryExportNormaliser;
use Inachis\Service\Export\ExportWriterInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Service for exporting categories. The service uses the {@link CategoryRepository} to retrieve categories,
 * and the {@link CategoryExportNormaliser} to normalise them. The service uses the {@link CategoryExportWriter}
 * interface to write the categories to a file of a given type (JSON/XML).
 */
final class CategoryExportService extends AbstractExportService
{
    /**
     * @param CategoryRepository $repository The repository to use for categories operations.
     * @param CategoryExportNormaliser $normaliser The normaliser to use.
     * @param iterable<ExportWriterInterface> $writers The writers to use.
     */
    public function __construct(
        private CategoryRepository $repository,
        private CategoryExportNormaliser $normaliser,
        #[AutowireIterator('inachis.export_writer')] iterable $writers,
    ) {
        parent::__construct($writers);
    }

    /**
     * Export categories to a file of a given type (JSON/XML).
     *
     * @param iterable<Category> $categories The categories to export.
     * @param string $format The format to export to (json/xml).
     * @return string The exported categories.
     */
    public function export(?iterable $categories = null, string $format = 'json'): string
    {
        $categories ??= $this->repository->findAll();
        return $this->exportCollection($categories, $format, 'category');
    }

    /**
     * Normalise a category.
     *
     * @param object $category The category to normalise.
     * @return object The normalised category.
     */
    protected function normalise(object $category): object
    {
        return $this->normaliser->normalise($category);
    }

    /**
     * Get categories by IDs via the repository.
     *
     * @param array $ids The IDs of the categories to retrieve.
     * @return iterable<Category> The categories.
     */
    public function getCategoriesByIds(array $ids): iterable
    {
        return $this->repository->getFilteredIds($ids);
    }

    /**
     * Get all categories via the repository.
     *
     * @return iterable<Category> The categories.
     */
    public function getAllCategories(): iterable
    {
        return $this->repository->findAll();
    }
}
