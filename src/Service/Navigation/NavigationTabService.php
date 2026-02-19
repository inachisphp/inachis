<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Navigation;

use Inachis\Entity\NavigationTab;
use Inachis\Repository\NavigationTabRepository;
use Inachis\Service\Doctrine\TransactionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for managing navigation tabs
 */
class NavigationTabService
{
    private const CACHE_KEY = 'navigation_tabs';

    /**
     * Constructor
     *
     * @param NavigationTabRepository $repository
     * @param CacheInterface $cache
     */
    public function __construct(
        private NavigationTabRepository $repository,
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
        private TransactionHelper $transactionHelper,
    ) {}

    /**
     * Get all active navigation tabs ordered by position
     *
     * @return array
     */
    public function getActiveTabs(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->repository->findActiveOrdered();
        });
    }

    /**
     * Clear the navigation cache
     */
    public function clearCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * Add or update a navigation tab
     *
     * @param NavigationTab $tab
     * @return void
     */
    public function add(NavigationTab $tab): void
    {
        $this->transactionHelper->executeInTransaction(function () use ($tab) {
            $maxPosition = $this->repository->getMaxPosition();
            $tab->setPosition($maxPosition + 1);

            if ($tab->getId() === null) {
                $this->entityManager->persist($tab);
            }

            // Normalise positions in case there are gaps
            $tabs = $this->repository->getAllOrdered();
            $tabs[] = $tab;
            $this->normalisePositionsIndexed($tabs);

            $this->entityManager->flush();
            $this->clearCache();
        });
    }

    /**
     * Move a tab up or down
     *
     * @param NavigationTab $tab
     * @param int $direction -1 for up, 1 for down
     */
    private function move(NavigationTab $tab, int $direction): void
    {
        $tabs = $this->repository->getAllOrdered();

        foreach ($tabs as $index => $item) {
            if ($item->getId()->equals($tab->getId())) {
                $swapIndex = $index + $direction;
                if ($swapIndex >= 0 && $swapIndex < count($tabs)) {
                    $this->swapPositions($item, $tabs[$swapIndex]);
                    break;
                }
            }
        }

        $this->transactionHelper->executeInTransaction(function () use ($tabs) {
            $this->normalisePositionsIndexed($tabs);
            $this->entityManager->flush();
            $this->clearCache();
        });
    }

    /**
     * Move a navigation tab up
     *
     * @param NavigationTab $tab
     * @return void
     */
    public function moveUp(NavigationTab $tab): void
    {
         $this->move($tab, -1);
    }

    /**
     * Move a navigation tab down
     *
     * @param NavigationTab $tab
     * @return void
     */
    public function moveDown(NavigationTab $tab): void
    {
        $this->move($tab, 1);
    }

    /**
     * Swap the positions of two navigation tabs
     *
     * @param NavigationTab $a
     * @param NavigationTab $b
     * @return void
     */
    private function swapPositions(NavigationTab $a, NavigationTab $b): void
    {
        $posA = $a->getPosition();
        $a->setPosition($b->getPosition());
        $b->setPosition($posA);
    }

    /**
     * Reorder navigation tabs based on input data
     *
     * @param array<int, array{id?: string, position?: int}> $data
     * @return bool True if any changes were made
     */
    public function reorderTabs(array $data): bool
    {
        $tabs = $this->repository->findAllIndexedById();
        $updated = false;

        $this->transactionHelper->executeInTransaction(function () use ($data, $tabs, &$updated) {
            foreach ($data as $item) {
                $id = (string) ($item['id'] ?? '');
                $position = isset($item['position']) ? (int) $item['position'] : null;

                if ($id && isset($tabs[$id]) && $position !== null) {
                    if ($tabs[$id]->getPosition() !== $position) {
                        $tabs[$id]->setPosition($position);
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $this->normalisePositionsIndexed($tabs);
                $this->entityManager->flush();
                $this->clearCache();
            }
        });

        return $updated;
    }


    /**
     * Normalise the positions of all navigation tabs
     */
    public function normalisePositions(): void
    {
        $tabs = $this->repository->getAllOrdered();
        $this->normalisePositionsIndexed(array_combine(
            array_map(fn($t) => $t->getId(), $tabs),
            $tabs
        ));
        
        $this->entityManager->flush();
        $this->clearCache();
    }

    /**
     * Normalise positions of given tabs in memory to be 1..n
     *
     * @param array<string, NavigationTab> $tabs
     */
    private function normalisePositionsIndexed(array $tabs): void
    {
        usort($tabs, fn(NavigationTab $a, NavigationTab $b) => $a->getPosition() <=> $b->getPosition());

        foreach ($tabs as $index => $tab) {
            $tab->setPosition($index + 1);
        }
    }

    /**
     * Delete navigation tabs
     *
     * @param array<string> $ids
     */
    private function delete(NavigationTab $tab): void
    {
        $this->entityManager->remove($tab);
    }

    /**
     * Apply an action to navigation tabs
     * 
     * @param string $action
     * @param array<string> $ids
     * @return int
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        $this->transactionHelper->executeInTransaction(function () use ($ids, $action, &$count) {
            foreach ($ids as $id) {
                $tab = $this->repository->findOneBy(['id' => $id]);
                if (empty($tab->getId())) {
                    continue;
                }
                match ($action) {
                    'delete'  => $this->delete($tab),
                    'enable'  => $tab->setIsActive(true),
                    'disable' => $tab->setIsActive(false),
                    default   => null,
                };
                $count++;
            }
            $this->entityManager->flush();
            $this->clearCache();
        });
            
        return $count;
    }
}