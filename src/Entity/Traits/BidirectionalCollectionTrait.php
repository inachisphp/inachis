<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\Traits;

use Doctrine\Common\Collections\Collection;

trait BidirectionalCollectionTrait
{
    /**
     * Add items to both sides of Many-to-Many relationship.
     *
     * @param Collection<int,\Inachis\Entity\Content\Page>|Collection<int,\Inachis\Entity\Content\Series> $collection
     * @param \Inachis\Entity\Content\Page|\Inachis\Entity\Content\Series                                 $item
     */
    private function addBidirectional(
        Collection $collection,
        object $item,
        string $owningMethod,
    ): void {
        if (!$collection->contains($item)) {
            $collection->add($item);

            if (method_exists($item, $owningMethod)) {
                $item->{$owningMethod}($this);
            }
        }
    }

    /**
     * Removes items from both sides of Many-to-Many relationship.
     *
     * @param Collection<int,\Inachis\Entity\Content\Page>|Collection<int,\Inachis\Entity\Content\Series> $collection
     * @param \Inachis\Entity\Content\Page|\Inachis\Entity\Content\Series                                 $item
     */
    private function removeBidirectional(
        Collection $collection,
        object $item,
        string $owningMethod,
    ): void {
        if ($collection->removeElement($item)) {
            if (method_exists($item, $owningMethod)) {
                $item->{$owningMethod}($this);
            }
        }
    }
}
