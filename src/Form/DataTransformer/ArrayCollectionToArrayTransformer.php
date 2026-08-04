<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ArrayCollectionToArrayTransformer.
 *
 * @implements DataTransformerInterface<
 *     ArrayCollection<int, mixed>,
 *     array<int, mixed>
 * >
 */
class ArrayCollectionToArrayTransformer implements DataTransformerInterface
{
    /**
     * Transform the value from an ArrayCollection into an array.
     *
     * @param ArrayCollection<int, mixed>|null $value
     *
     * @return array<int, mixed>
     */
    public function transform(mixed $value): array
    {
        return $value instanceof ArrayCollection
            ? $value->toArray()
            : [];
    }

    /**
     * Transform the array of values into an ArrayCollection.
     *
     * @param array<int, mixed> $value
     *
     * @return ArrayCollection<int, mixed>
     */
    public function reverseTransform(mixed $value): ArrayCollection
    {
        return new ArrayCollection($value);
    }
}
