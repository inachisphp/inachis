<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * @param array $values
     * @return mixed
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
     * @param mixed $object The object to hydrate
     * @param array $values The values to apply to the object
     * @return mixed The hydrated object
     */
    public function hydrate(mixed $object, array $values): mixed
    {
        if (!is_object($object)) {
            return $object;
        }
        foreach ($values as $key => $value) {
            $methodName = 'set' . ucfirst($key);
            if (method_exists($object, $methodName) && !($key === 'id' && $value === '-1')) {
                $object->$methodName($value);
            }
        }

        return $object;
    }

    /**
     * Returns the count for entries in the current repository match any
     * provided constraints.
     *
     * @param string[] $where Array of elements and string replacements
     * @return int The number of entities located
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
     * Returns all entries for the current repository.
     *
     * @param int $offset The offset from which to return results from
     * @param int $limit  The maximum number of results to return
     * @param list<int, array<int, string>> $where
     * @param list<int, array<int, string>>|string $order
     * @param list<int, array<int, string>>|string $groupBy
     * @param list<int, array<int, string>> $join
     * @return Paginator The result of fetching the objects
     */
    public function getAll(
        int $offset = 0,
        int $limit = 25,
        array $where = [],
        array|string $order = [],
        array|string $groupBy = [],
        array $join = []
    ): Paginator {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('q')
            ->from($this->getClassName(), 'q');
        if (!empty($join)) {
            if (count($join) === 2) {
                $qb->join($join[0], $join[1]);
            } else {
                foreach ($join as $j) {
                    if (count($j) >= 3) {
                        $type = $j[0]; // 'join' or 'leftJoin'
                        $path = $j[1]; // e.g., 'q.items'
                        $alias = $j[2]; // e.g., 'i'
                        $condition = $j[3] ?? null;

                        if ($type === 'join') {
                            $condition ? $qb->join($path, $alias, 'WITH', $condition)
                                    : $qb->join($path, $alias);
                        } elseif ($type === 'leftJoin') {
                            $condition ? $qb->leftJoin($path, $alias, 'WITH', $condition)
                                    : $qb->leftJoin($path, $alias);
                        }
                    }
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
                $qb = $qb->setParameter($key, $value);
            }
        }
        if (!empty($groupBy)) {
            foreach ($groupBy as $group) {
                $qb->addGroupBy($group);
            }
        }

        $query = $qb->getQuery();
        if ($offset > 0) {
            $query = $query->setFirstResult($offset);
        }
        if ($limit > 0) {
            $query = $query->setMaxResults($limit);
        }

        return new Paginator($query, false);
    }

    /**
     * Returns the maximum number of items to show
     *
     * @return int
     */
    public function getMaxItemsToShow(): int
    {
        // @todo check if an alternative is set in yaml config
        return defined('static::MAX_ITEMS_TO_SHOW_ADMIN') ? (int) static::MAX_ITEMS_TO_SHOW_ADMIN : 10;
    }
}
