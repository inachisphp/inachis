<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

use Inachis\Entity\Content\Category;
use Inachis\Repository\Content\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContentQueryParameters class
 */
class ContentQueryParameters
{
    /**
     * Constructor for ContentQueryParameters class
     *
     * @param array{filters: array<string,mixed>, sort: string, offset: int, limit: int}|array{} $filters
     * @param string $sort
     * @param int $limit
     * @param int $offset
     */
    public function __construct(
        protected array $filters = [],
        protected string $sort = '',
        protected int $limit = 10,
        protected int $offset = 0,
    ) {}

    /**
     * Process the request and return the query parameters
     *
     * @param Request $request
     * @param CategoryRepository $categoryRepository
     * @param string $prefix
     * @param string $sortDefault
     * @return array{filters: array<string,mixed>|array{}, sort: string, offset: int, limit: int}
     */
    public function process (
        Request $request,
        CategoryRepository $categoryRepository,
        string $prefix = '',
        string $sortDefault = '',
    ): array {
        $this->filters = array_filter($request->request->all('filter'));
        $this->sort = $request->request->getString('sort') ?: $sortDefault;

        if (isset($this->filters['categories']) && is_array($this->filters['categories']) && array_is_list($this->filters['categories'])) {
            /** @var list<Category> */
            $categories = $categoryRepository->findBy(['id' => $this->filters['categories']]);
            $categoryFilter = [];
            foreach ($categories as $category) {
                $id = $category->getId()?->toString() ?: '';
                if (!empty($id)) {
                    $categoryFilter[$id] = $category->getTitle();
                }
            }
            $this->filters['categories'] = $categoryFilter;
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $request->getSession()->set($prefix . '_filters', $this->filters);
            $request->getSession()->set($prefix . '_sort', $this->sort);
        } elseif ($request->getSession()->has($prefix . '_filters')) {
            $this->filters = $request->getSession()->get($prefix . '_filters', '');
            $sort = $request->getSession()->get($prefix . '_sort', '');
            $this->sort = is_string($sort) ? $sort : '';
        }
        $limit = $request->attributes->getInt(
            'limit',
            $categoryRepository->getMaxItemsToShow(),
        );
        $offset = $request->attributes->getInt('offset', 0);
        $this->limit = is_numeric($limit) ? (int) $limit : 10;
        $this->offset = is_numeric($offset) ? (int) $offset : 0;

        return [
            'filters' => $this->filters,
            'sort' => $this->sort,
            'offset' => $this->offset,
            'limit' => $this->limit,
        ];
    }
}
