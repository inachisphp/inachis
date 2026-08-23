<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Import\Category;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Category;
use Inachis\Model\CategoryExportDto;
use Inachis\Model\Import\CategoryImportResult;
use Inachis\Repository\Content\CategoryRepository;

/**
 * Service for importing categories from DTOs and building a tree.
 */
final class CategoryImportService
{
    /**
     * Constructor for CategoryImportService.
     */
    public function __construct(
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Build or update category tree from DTOs.
     *
     * @param iterable<CategoryExportDto> $categoryDtos
     */
    public function import(iterable $categoryDtos): CategoryImportResult
    {
        $result = new CategoryImportResult();
        $existingCategories = [];

        $this->entityManager->beginTransaction();

        try {
            // Map existing categories by full path
            foreach ($this->categoryRepository->findAll() as $cat) {
                $existingCategories[$cat->getFullPath()] = $cat;
            }

            // Iterate over DTOs
            foreach ($categoryDtos as $dto) {
                /** @var CategoryExportDto $dto */
                $parent = null;

                if (!is_string($dto->fullPath) || empty($dto->fullPath)) {
                    continue;
                }

                // Rebuild hierarchy from fullPath
                $segments = explode('/', $dto->fullPath);
                $pathSoFar = '';

                foreach ($segments as $title) {
                    $pathSoFar = $pathSoFar ? $pathSoFar.'/'.$title : $title;

                    if (isset($existingCategories[$pathSoFar])) {
                        $cat = $existingCategories[$pathSoFar];
                    } else {
                        $cat = new Category($title);
                        $cat->setParent($parent);
                        $this->entityManager->persist($cat);
                        ++$result->categoriesCreated;
                        $existingCategories[$pathSoFar] = $cat;
                    }

                    $parent = $cat;
                }

                $cat->setDescription($dto->description ?? $cat->getDescription());
                $cat->setVisible($dto->visible);
            }
            $uow = $this->entityManager->getUnitOfWork();
            foreach ($existingCategories as $category) {
                if (!empty($uow->getEntityChangeSet($category))) {
                    ++$result->categoriesUpdated;
                }
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Import failed: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Maps the imported data to DTOs.
     *
     * @param list<array{
     *     id?: string|null,
     *     title?: string|null,
     *     fullPath?: string|null,
     *     description?: string|null,
     *     visible?: bool|null,
     *     image?: string|null,
     *     icon?: string|null
     * }> $data
     *
     * @return list<CategoryExportDto>
     */
    public function mapToDto(array $data): array
    {
        $dtos = [];

        foreach ($data as $category) {
            $dto = new CategoryExportDto();
            $dto->id = isset($category['id']) ? (string) $category['id'] : null;
            $dto->title = (string) ($category['title'] ?? '');
            $dto->fullPath = isset($category['fullPath'])
                ? (string) $category['fullPath']
                : (isset($category['title']) ? (string) $category['title'] : '');
            $dto->description = isset($category['description']) ? (string) $category['description'] : null;
            $dto->visible = (bool) ($category['visible'] ?? true);
            $dto->image = isset($category['image']) ? (string) $category['image'] : null;
            $dto->icon = isset($category['icon']) ? (string) $category['icon'] : null;

            $dtos[] = $dto;
        }

        return $dtos;
    }
}
