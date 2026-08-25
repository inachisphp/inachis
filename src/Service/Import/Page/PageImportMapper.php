<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Service\Import\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Tag;
use Inachis\Model\Page\CategoryPathDto;
use Inachis\Model\Page\PageExportDto;
use Inachis\Model\Page\TagDto;
use Inachis\Model\Page\UrlDto;

/**
 * Mapper for importing pages.
 */
final class PageImportMapper
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Resolve or create category chain from full path.
     *
     * @return Category the last Category in the path
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
     *
     * @return Tag the tag
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

    /**
     * Map raw page item array to PageExportDto.
     *
     * @param array<string, mixed> $item
     */
    public function mapItemToDto(array $item): PageExportDto
    {
        $dto = new PageExportDto();
        $dto->title = is_scalar($item['title'] ?? null) ? (string) $item['title'] : '';
        $dto->content = is_scalar($item['content'] ?? null) ? (string) $item['content'] : '';
        $dto->subTitle = is_scalar($item['subTitle'] ?? null) ? (string) $item['subTitle'] : null;
        $dto->type = is_scalar($item['type'] ?? null) ? (string) $item['type'] : 'post';
        $dto->status = is_scalar($item['status'] ?? null) ? (string) $item['status'] : 'draft';
        $dto->postDate = is_scalar($item['postDate'] ?? null) ? (string) $item['postDate'] : null;
        $dto->visible = (bool) ($item['visible'] ?? true);
        $dto->allowComments = (bool) ($item['allowComments'] ?? false);
        $dto->language = is_scalar($item['language'] ?? null) ? (string) $item['language'] : null;
        $dto->timezone = is_scalar($item['timezone'] ?? null) ? (string) $item['timezone'] : null;

        // Categories
        if (isset($item['categories']) && is_array($item['categories'])) {
            foreach ($item['categories'] as $cat) {
                $rawPath = is_array($cat) ? ($cat['path'] ?? '') : $cat;
                $path = is_scalar($rawPath) ? (string) $rawPath : '';
                if ('' !== $path) {
                    $catDto = new CategoryPathDto();
                    $catDto->path = $path;
                    $dto->categories[] = $catDto;
                }
            }
        } elseif (isset($item['category']) && is_scalar($item['category'])) {
            $catDto = new CategoryPathDto();
            $catDto->path = (string) $item['category'];
            $dto->categories[] = $catDto;
        }

        // Tags
        if (isset($item['tags']) && is_array($item['tags'])) {
            foreach ($item['tags'] as $tag) {
                $rawTitle = is_array($tag) ? ($tag['title'] ?? '') : $tag;
                $title = is_scalar($rawTitle) ? (string) $rawTitle : '';
                if ('' !== $title) {
                    $tagDto = new TagDto();
                    $tagDto->title = $title;
                    $dto->tags[] = $tagDto;
                }
            }
        } elseif (isset($item['tag']) && is_scalar($item['tag'])) {
            $tagDto = new TagDto();
            $tagDto->title = (string) $item['tag'];
            $dto->tags[] = $tagDto;
        }

        // URLs
        if (isset($item['urls']) && is_array($item['urls'])) {
            foreach ($item['urls'] as $url) {
                $rawPath = is_array($url) ? ($url['path'] ?? $url['link'] ?? '') : $url;
                $path = is_scalar($rawPath) ? (string) $rawPath : '';
                $default = is_array($url) ? (bool) ($url['default'] ?? true) : true;
                if ('' !== $path) {
                    $urlDto = new UrlDto();
                    $urlDto->path = $path;
                    $urlDto->default = $default;
                    $dto->urls[] = $urlDto;
                }
            }
        } elseif (isset($item['url']) && is_scalar($item['url']) && '' !== (string) $item['url']) {
            $urlDto = new UrlDto();
            $urlDto->path = (string) $item['url'];
            $urlDto->default = true;
            $dto->urls[] = $urlDto;
        }

        return $dto;
    }

    /**
     * Map multiple raw page item arrays to PageExportDto array.
     *
     * @param list<array<string, mixed>> $data
     *
     * @return list<PageExportDto>
     */
    public function mapToDto(array $data): array
    {
        return array_map([$this, 'mapItemToDto'], $data);
    }
}
