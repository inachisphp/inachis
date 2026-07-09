<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

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
     * @param string $view
     */
    public function __construct(
        protected array $filters = [],
        protected string $sort = '',
        protected int $limit = 10,
        protected int $offset = 0,
        protected string $view = 'list',
    ) {}

    /**
     * Creates a new instance using the current values as defaults and
     * overriding them with any values supplied in the request.
     */
    public static function fromRequest(
        Request $request,
        self $current,
    ): self {

        $filters = array_filter(
            $request->request->all('filter')
        );

        return new self(
            filters: $filters !== []
                ? $filters
                : $current->getFilters(),

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
