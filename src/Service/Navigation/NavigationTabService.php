<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Navigation;

use Inachis\Entity\System\NavigationTab;
use Inachis\Model\NavigationTabDto;
use Inachis\Repository\System\NavigationTabRepository;
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
     * @param EntityManagerInterface $entityManager
     * @param TransactionHelper $transactionHelper
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
     * @return list<NavigationTabDto>
     */
    public function getActiveTabs(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $item->expiresAfter(null);
            return $this->repository->findActiveOrderedModels();
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

            if ($tab->getId() === null) {
                $tab->setPosition(
                    $this->repository->getMaxPosition() + 1
                );

                $this->entityManager->persist($tab);
            }

            $this->entityManager->flush();
            $this->clearCache();
        });
    }

    /**
     * Reassign positions safely while preserving the unique constraint.
     *
     * @param NavigationTab[] $orderedTabs
     */
    private function applyOrderedPositions(array $orderedTabs): void
    {
        // Phase 1: move everything out of the way
        foreach ($orderedTabs as $index => $tab) {
            $tab->setPosition(1000 + $index);
        }

        $this->entityManager->flush();

        // Phase 2: assign final positions
        foreach ($orderedTabs as $index => $tab) {
            $tab->setPosition($index + 1);
        }

        $this->entityManager->flush();
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
            if ($item->getId()?->equals($tab->getId())) {
                $swapIndex = $index + $direction;

                if ($swapIndex >= 0 && $swapIndex < count($tabs)) {
                    [$tabs[$index], $tabs[$swapIndex]] =
                        [$tabs[$swapIndex], $tabs[$index]];
                }

                break;
            }
        }

        $this->transactionHelper->executeInTransaction(function () use ($tabs) {
            $this->applyOrderedPositions($tabs);
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
     * Reorder navigation tabs based on input data
     *
     * @param array{id?: string, order?: list<string>} $data
     * @return bool True if any changes were made
     */
    public function reorderTabs(array $data): bool
    {
        if (!isset($data['order'])) {
            return false;
        }

        $tabsById = $this->repository->findAllIndexedById();

        $orderedTabs = [];

        foreach ($data['order'] as $id) {
            if (isset($tabsById[$id])) {
                $orderedTabs[] = $tabsById[$id];
            }
        }

        if (count($orderedTabs) !== count($tabsById)) {
            return false;
        }

        $changed = false;

        foreach ($orderedTabs as $index => $tab) {
            if ($tab->getPosition() !== ($index + 1)) {
                $changed = true;
                break;
            }
        }

        if (!$changed) {
            return false;
        }

        $this->transactionHelper->executeInTransaction(function () use ($orderedTabs) {
            $this->applyOrderedPositions($orderedTabs);
            $this->clearCache();
        });

        return true;
    }


    /**
     * Normalise the positions of all navigation tabs
     */
    public function normalisePositions(): void
    {
        $tabs = $this->repository->getAllOrdered();

        $this->transactionHelper->executeInTransaction(function () use ($tabs) {
            $this->applyOrderedPositions($tabs);
            $this->clearCache();
        });
    }

    /**
     * Delete navigation tabs
     *
     * @param NavigationTab $tab
     */
    private function delete(NavigationTab $tab): void
    {
        if ($tab->isSystem()) {
            return;
        }
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
                /** @var NavigationTab|null $tab */
                $tab = $this->repository->findOneBy(['id' => $id]);
                if (!$tab || !$tab->getId()) {
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
