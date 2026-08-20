<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export\Category;

use Inachis\Entity\Content\Category;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Service\Export\AbstractExportService;
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
     * @param CategoryRepository              $repository the repository to use for categories operations
     * @param CategoryExportNormaliser        $normaliser the normaliser to use
     * @param iterable<ExportWriterInterface> $writers    the writers to use
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
     * @param iterable<Category> $categories the categories to export
     * @param string             $format     the format to export to (json/xml)
     *
     * @return string the exported categories
     */
    public function export(?iterable $categories = null, string $format = 'json'): string
    {
        $categories ??= $this->repository->findAll();

        return $this->exportCollection($categories, $format, 'category');
    }

    /**
     * Normalise a category.
     *
     * @param object $category the category to normalise
     *
     * @return object the normalised category
     */
    protected function normalise(object $category): object
    {
        return $this->normaliser->normalise($category);
    }

    /**
     * Get categories by IDs via the repository.
     *
     * @param array $ids the IDs of the categories to retrieve
     *
     * @return iterable<Category> the categories
     */
    public function getCategoriesByIds(array $ids): iterable
    {
        return $this->repository->getFilteredIds($ids);
    }

    /**
     * Get all categories via the repository.
     *
     * @return iterable<Category> the categories
     */
    public function getAllCategories(): iterable
    {
        return $this->repository->findAll();
    }
}
