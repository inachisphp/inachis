<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    /** @var int */
    public const MAX_ITEMS_TO_SHOW_ADMIN = 10;

    /**
     * @param array<string,mixed> $values
     */
    public function create(array $values = []): mixed
    {
        $objectType = $this->getClassName();

        return $this->hydrate(new $objectType(), $values);
    }

    /**
     * Uses the objects setters to populate the object
     * based on the provided values.
     *
     * @param mixed               $object The object to hydrate
     * @param array<string,mixed> $values The values to apply to the object
     *
     * @return mixed The hydrated object
     */
    public function hydrate(mixed $object, array $values): mixed
    {
        if (!is_object($object)) {
            return $object;
        }
        foreach ($values as $key => $value) {
            $methodName = 'set'.ucfirst($key);
            if (method_exists($object, $methodName) && !('id' === $key && '-1' === $value)) {
                $object->$methodName($value);
            }
        }

        return $object;
    }

    /**
     * Returns the count for entries in the current repository match any
     * provided constraints.
     *
     * @param array{0:string, 1?:array<string,string|list<string>>}|array{} $where Array of elements and string replacements
     *
     * @return int The number of entities located
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getAllCount(array $where = []): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('count(q.id)')
            ->from($this->getClassName(), 'q');
        if (!empty($where)) {
            $qb->where($where[0]);
            if (isset($where[1])) {
                foreach ($where[1] as $field => $value) {
                    $qb->setParameter($field, $value);
                }
            }
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns all entries for the current repository using Paginator; if you don't need pagination,
     * do NOT use this function - use a findAll instead.
     *
     * @param int                                                            $limit   The maximum number of results to return
     * @param int                                                            $offset  The offset from which to return results from
     * @param list{0: string, 1?:array<string, mixed>}|list{}                $where
     * @param list<list{0: string, 1: string}>|string|list{}                 $order
     * @param list<string>|list{}                                            $groupBy
     * @param list<list{0: string, 1: string, 2: string, 3?: string}>|list{} $join
     *
     * @return Paginator<T> The result of fetching the objects
     */
    public function getAll(
        int $limit = 25,
        int $offset = 0,
        array $where = [],
        array|string $order = [],
        array $groupBy = [],
        array $join = [],
    ): Paginator {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('q')
            ->from($this->getClassName(), 'q');
        if (!empty($join)) {
            foreach ($join as $j) {
                $type = $j[0]; // 'join' or 'leftJoin'
                $path = $j[1]; // e.g., 'q.items'
                $alias = $j[2]; // e.g., 'i'
                $condition = $j[3] ?? null;

                if ('join' === $type) {
                    $condition
                        ? $qb->join($path, $alias, 'ON', $condition)
                        : $qb->join($path, $alias);
                } elseif ('leftJoin' === $type) {
                    $condition
                        ? $qb->leftJoin($path, $alias, 'ON', $condition)
                        : $qb->leftJoin($path, $alias);
                }
            }
        }
        if (!empty($where)) {
            $qb = $qb->where($where[0]);
        }
        if (!empty($order)) {
            if (is_array($order)) {
                foreach ($order as $orderOption) {
                    $qb = $qb->addOrderBy($orderOption[0], $orderOption[1]);
                }
            }
            if (is_string($order)) {
                $qb = $qb->orderBy($order);
            }
        }
        if (!empty($where[1])) {
            foreach ($where[1] as $key => $value) {
                $paramValue = $value;
                $paramType = null;

                // support typed parameters: [value, type]
                if (is_array($value)) {
                    $paramValue = $value['value'];
                    $paramType = !empty($value['type']) && is_string($value['type']) ?
                        $value['type'] :
                        null;
                }
                if (null !== $paramType) {
                    $qb = $qb->setParameter($key, $paramValue, $paramType);
                } else {
                    $qb = $qb->setParameter($key, $paramValue);
                }
            }
        }
        if (!empty($groupBy)) {
            foreach ($groupBy as $group) {
                $qb->addGroupBy($group);
            }
        }

        $query = $qb->getQuery();
        if ($limit > 0) {
            $query = $query->setMaxResults($limit);
        }
        if ($offset > 0) {
            $query = $query->setFirstResult($offset);
        }

        /* @var Paginator<T> */
        return new Paginator($query, false);
    }

    /**
     * Returns the maximum number of items to show.
     */
    public function getMaxItemsToShow(): int
    {
        // TODO: check if an alternative is set in yaml config
        return static::MAX_ITEMS_TO_SHOW_ADMIN;
    }
}
