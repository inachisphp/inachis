<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model;

use Inachis\Entity\Content\Category;
use Inachis\Repository\Content\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContentQueryParameters class.
 */
class ContentQueryParameters
{
    /**
     * Constructor for ContentQueryParameters class.
     *
     * @param array{
     *     filters: array<string,mixed>,
     *     sort: string,
     *     limit: int,
     *     offset: int
     * }|array{} $filters
     */
    public function __construct(
        protected array $filters = [],
        protected string $sort = '',
        protected int $limit = 10,
        protected int $offset = 0,
        protected string $view = 'list',
    ) {
    }

    /**
     * Creates a new instance using the current values as defaults and
     * overriding them with any values supplied in the request.
     */
    public static function fromRequest(
        Request $request,
        self $current,
        CategoryRepository $categoryRepository,
    ): self {
        $filters = $current->getFilters();

        /*
        * If the filter form was submitted, replace the existing filters.
        * This allows filters to be cleared.
        */
        if ($request->request->has('filter')) {
            $filters = $request->request->all('filter');

            /*
            * Normalise empty category selection from Tom Select.
            */
            if (($filters['categories'] ?? null) === '') {
                $filters['categories'] = [];
            }

            /*
            * Remove empty scalar filters.
            */
            $filters = array_filter(
                $filters,
                static fn (mixed $value): bool => '' !== $value && [] !== $value,
            );

            /*
            * Convert category UUIDs into category labels.
            */
            if (
                isset($filters['categories'])
                && is_array($filters['categories'])
                && array_is_list($filters['categories'])
            ) {
                /** @var list<Category> $categories */
                $categories = $categoryRepository->findBy([
                    'id' => $filters['categories'],
                ]);

                $categoryFilter = [];

                foreach ($categories as $category) {
                    $id = $category->getId()?->toString();

                    if (null !== $id) {
                        $categoryFilter[$id] = $category->getTitle();
                    }
                }

                $filters['categories'] = $categoryFilter;
            }
        }

        return new self(
            filters: $filters,
            sort: $request->request->getString('sort')
                ?: $current->getSort(),

            limit: $request->attributes->getInt(
                'limit',
                $current->getLimit(),
            ),

            offset: $request->attributes->getInt(
                'offset',
                $current->getOffset(),
            ),

            view: $request->request->getString('view')
                ?: $current->getView(),
        );
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'sort' => $this->sort,
            'offset' => $this->offset,
            'limit' => $this->limit,
            'view' => $this->view,
        ];
    }
}
