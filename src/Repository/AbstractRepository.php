<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\Log\LoggerInterface;
use Exception;

abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * @param array $values
     *
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
     * @param array[mixed] $values The values to apply to the obect
     *
     * @return mixed The hydrated object
     */
    public function hydrate(mixed $object, array $values): mixed
    {
        if (!is_object($object)) {
            return $object;
        }
        foreach ($values as $key => $value) {
            $methodName = 'set' . ucfirst($key);
            if (method_exists($object, $methodName)) {
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
                $qb->setParameters($where[1]);
            }
        }

        return (int) $qb
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns all entries for the current repository.
     *
     * @param int          $offset The offset from which to return results from
     * @param int          $limit  The maximum number of results to return
     * @param array        $where
     * @param array|string $order
     * @param array|string $groupBy
     *
     * @return Paginator The result of fetching the objects
     */
    public function getAll(
        int $offset = 0,
        int $limit = 25,
        array $where = [],
        array|string $order = [],
        array|string $groupBy = []
    ): Paginator {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('q')
            ->from($this->getClassName(), 'q');
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
            $qb = $qb->setParameters($where[1]);
        }
        if (!empty($groupBy)) {
            foreach ($groupBy as $group) {
                $qb->addGroupBy($group);
            }
        }

        $qb = $qb->getQuery();
        if ($offset > 0) {
            $qb = $qb->setFirstResult($offset);
        }
        if ($limit > 0) {
            $qb = $qb->setMaxResults($limit);
        }

        return new Paginator($qb, false);
    }

    /**
     * @return int
     */
    public function getMaxItemsToShow(): int
    {
        // @todo check if an alternative is set in yaml config
        return defined('static::MAX_ITEMS_TO_SHOW_ADMIN') ? (int) static::MAX_ITEMS_TO_SHOW_ADMIN : 10;
    }

    /**
     * @param LoggerInterface $logger
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function wipe(LoggerInterface $logger): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');
            $connection->query(
                'DELETE FROM ' .
                $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName()
            );
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->commit();
        } catch (Exception $e) {
            $logger->error(sprintf('Failed to wipe table: %s', $e->getTraceAsString()));
            $connection->rollBack();
        }
    }
}
