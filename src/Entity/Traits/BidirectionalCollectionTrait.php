<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Entity\Traits;

use Doctrine\Common\Collections\Collection;

trait BidirectionalCollectionTrait
{
    /**
     * Add items to both sides of Many-to-Many relationship
     *
     * @param Collection<int,\Inachis\Entity\Content\Page>|Collection<int,\Inachis\Entity\Content\Series> $collection
     * @param \Inachis\Entity\Content\Page|\Inachis\Entity\Content\Series $item
     * @param string $owningMethod
     */
    private function addBidirectional(
        Collection $collection,
        object $item,
        string $owningMethod
    ): void {
        if (!$collection->contains($item)) {
            $collection->add($item);

            if (method_exists($item, $owningMethod)) {
                $item->{$owningMethod}($this);
            }
        }
    }

    /**
     * Removes items from both sides of Many-to-Many relationship
     *
     * @param Collection<int,\Inachis\Entity\Content\Page>|Collection<int,\Inachis\Entity\Content\Series> $collection
     * @param \Inachis\Entity\Content\Page|\Inachis\Entity\Content\Series $item
     * @param string $owningMethod
     * @return void
     */
    private function removeBidirectional(
        Collection $collection,
        object $item,
        string $owningMethod
    ): void {
        if ($collection->removeElement($item)) {
            if (method_exists($item, $owningMethod)) {
                $item->{$owningMethod}($this);
            }
        }
    }
}