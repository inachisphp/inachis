<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Model\Page;

final class ViewStateDefaults
{
    public function __construct(
        /** @var array<string,mixed> */
        public readonly array $filters = [],
        public readonly string $sort = '',
        public readonly string $view = 'list',
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getView(): string
    {
        return $this->view;
    }
}
