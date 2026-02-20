<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

/**
 * Wraps Doctrine transactions in a retry loop to handle optimistic locking exceptions
 * and unique constraint issues potentially caused by concurrency
 */
class TransactionHelper
{
    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * Execute a callback in a transaction with retry logic
     *
     * @param callable $callback
     * @param int $maxRetries
     * @return mixed
     */
    public function executeInTransaction(callable $callback, int $maxRetries = 3): mixed
    {
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $this->entityManager->wrapInTransaction(function () use ($callback, &$result) {
                    $result = $callback();
                });

                return $result;

            } catch (OptimisticLockException|UniqueConstraintViolationException $e) {
                $retryCount++;
                $this->entityManager->clear();

                if ($retryCount >= $maxRetries) {
                    throw $e;
                }
            } catch (ORMException $e) {
                throw $e;
            }
        }
        throw new \RuntimeException('Failed to execute transaction after maximum retries');
    }
}