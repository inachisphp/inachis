<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Model;

use Inachis\Entity\Content\Category;
use Inachis\Model\CategoryExportDto;
use PHPUnit\Framework\TestCase;

final class CategoryExportDtoTest extends TestCase
{
    public function testFromEntity()
    {
        $category = new Category('test category', 'description');

        $categoryDto = CategoryExportDto::fromEntity($category);
        $this->assertEquals($category->getTitle(), $categoryDto->title);
        $this->assertEquals($category->getDescription(), $categoryDto->description);
    }
}
