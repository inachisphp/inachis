<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Page;

final class ViewStateDefaults
{
    public function __construct(
        /** @var array<string,mixed> */
        public readonly array $filters = [],
        public readonly string $sort = '',
        public readonly string $view = 'list',
    ) {}

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
