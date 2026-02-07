<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Import\Category;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Category;
use Inachis\Model\CategoryExportDto;

/**
 * Service for importing categories from DTOs and building a tree.
 */
final class CategoryImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Build or update category tree from DTOs.
     *
     * @param CategoryExportDto[] $categoryDtos
     * @return CategoryImportResult
     */
    public function importTree(iterable $categoryDtos): CategoryImportResult
    {
        $result = new CategoryImportResult();
        $existingCategories = [];

        $this->entityManager->beginTransaction();

        try {
            // Map existing categories by full path
            foreach ($this->entityManager->getRepository(Category::class)->findAll() as $cat) {
                $existingCategories[$cat->getFullPath()] = $cat;
            }

            // Iterate over DTOs
            foreach ($categoryDtos as $dto) {
                /** @var CategoryExportDto $dto */
                $parent = null;

                // Rebuild hierarchy from fullPath
                $segments = explode('/', $dto->fullPath);
                $pathSoFar = '';

                foreach ($segments as $title) {
                    $pathSoFar = $pathSoFar ? $pathSoFar . '/' . $title : $title;

                    if (isset($existingCategories[$pathSoFar])) {
                        $cat = $existingCategories[$pathSoFar];
                    } else {
                        $cat = new Category($title);
                        $cat->setParent($parent);
                        $this->entityManager->persist($cat);
                        $result->categoriesCreated++;
                        $existingCategories[$pathSoFar] = $cat;
                    }

                    $parent = $cat;
                }

                // Optionally update description, visibility, image, icon
                $cat->setDescription($dto->description ?? $cat->getDescription());
                $cat->setVisible($dto->visible ?? $cat->isVisible());
                $cat->setImage($dto->image ?? $cat->getImage());
                $cat->setIcon($dto->icon ?? $cat->getIcon());
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            $result->warnings[] = 'Import failed: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Maps the imported data to DTOs.
     *
     * @param array $data
     * @return CategoryExportDto[]
     */
    public function mapToDto(array $data): array
    {
        $dtos = [];

        foreach ($data as $category) {
            $dto = new CategoryExportDto();
            $dto->id = $category['id'] ?? null; // optional for internal linking
            $dto->fullPath = $category['fullPath'] ?? ($category['title'] ?? '');
            $dto->description = $category['description'] ?? null;
            $dto->visible = $category['visible'] ?? true;
            $dto->image = $category['image'] ?? null;
            $dto->icon = $category['icon'] ?? null;

            $dtos[] = $dto;
        }

        return $dtos;
    }
}
