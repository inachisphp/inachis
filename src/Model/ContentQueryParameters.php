<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

use Inachis\Entity\Content\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContentQueryParameters class
 */
class ContentQueryParameters
{
    /**
     * Constructor for ContentQueryParameters class
     *
     * @param array $filters
     * @param string $sort
     * @param int $offset
     * @param int $limit
     */
    public function __construct(
        protected array $filters = [],
        protected string $sort = '',
        protected int $offset = 0,
        protected int $limit = 10
    ) {}

    /**
     * Process the request and return the query parameters
     *
     * @param Request $request
     * @param ServiceEntityRepositoryInterface $repository
     * @param string $prefix
     * @param string $sortDefault
     * @return array<string, mixed>
     */
    public function process (
        Request $request,
        ServiceEntityRepositoryInterface $repository,
        string $prefix = '',
        string $sortDefault = '',
    ): array {
        $this->filters = array_filter($request->request->all('filter', []));
        $this->sort = $request->request->get('sort', $sortDefault);

        if (isset($this->filters['categories']) && is_array($this->filters['categories']) && array_is_list($this->filters['categories'])) {
            if (method_exists($repository, 'getEntityManager')) {
                $categories = $repository->getEntityManager()->getRepository(Category::class)->findBy(['id' => $this->filters['categories']]);
                $categoryFilter = [];
                foreach ($categories as $category) {
                    $categoryFilter[$category->getId()->toString()] = $category->getTitle();
                }
                $this->filters['categories'] = $categoryFilter;
            }
        }

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
