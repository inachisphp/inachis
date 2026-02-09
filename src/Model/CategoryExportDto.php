<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

use Inachis\Entity\Category;

/**
 * Data Transfer Object for exporting categories.
 */
final class CategoryExportDto
{
    /**
     * The UUID as a string
     */
    public ?string $id = null;

    /**
     * The title of the category
     */
    public string $title = '';

    /**
     * The description of the category
     */
    public ?string $description = null;

    /**
     * The image URL/UUID
     */
    public ?string $image = null;

    /**
     * The icon URL/UUID
     */
    public ?string $icon = null;

    /**
     * Visibility flag
     */
    public bool $visible = true;

    /**
     * Parent category ID (nullable)
     */
    public ?string $parentId = null;

    /**
     * List of child categories IDs
     *
     * @var string[]
     */
    public array $childrenIds = [];

    /**
     * Optional: full path string
     */
    public ?string $fullPath = null;

    /**
     * Constructor from entity
     */
    public static function fromEntity(Category $category): self
    {
        $dto = new self();
        $dto->id = $category->getId()?->__toString();
        $dto->title = $category->getTitle() ?? '';
        $dto->description = $category->getDescription();
        $dto->image = $category->getImage()?->__toString() ?? null;
        $dto->icon = $category->getIcon()?->__toString() ?? null;
        $dto->visible = $category->isVisible();
        $dto->parentId = $category->getParent()?->getId()?->__toString();
        $dto->childrenIds = array_map(
            fn($child) => $child->getId()?->__toString(),
            $category->getChildren()->toArray()
        );
        $dto->fullPath = $category->getFullPath();

        return $dto;
    }
}
