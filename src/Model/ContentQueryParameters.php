<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Model;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

class ContentQueryParameters
{
    public function __construct(
        protected array $filters = [],
        protected string $sort = '',
        protected int $offset = 0,
        protected int $limit = 10
    ) {}

    public function process (
        Request $request,
        ServiceEntityRepositoryInterface $repository,
        string $prefix = '',
        string $sortDefault = '',
    ): array {
        $this->filters = array_filter($request->request->all('filter', []));
        $this->sort = $request->request->get('sort', $sortDefault);
        if ($request->isMethod(Request::METHOD_POST)) {
            $request->getSession()->set($prefix . '_filters', $this->filters);
            $request->getSession()->set($prefix . '_sort', $this->sort);
        } elseif ($request->getSession()->has($prefix . '_filters')) {
            $this->filters = $request->getSession()->get($prefix . '_filters', '');
            $this->sort = $request->getSession()->get($prefix . '_sort', '');
        }
        $this->offset = (int) $request->attributes->get('offset', 0);
        $this->limit = (int) $request->attributes->get(
            'limit',
            $repository->getMaxItemsToShow(),
        );

        return [
            'filters' => $this->filters,
            'sort' => $this->sort,
            'offset' => $this->offset,
            'limit' => $this->limit,
        ];
    }
}
