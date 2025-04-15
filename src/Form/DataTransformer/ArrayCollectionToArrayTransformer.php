<?php

namespace App\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class ArrayCollectionToArrayTransformer.
 */
class ArrayCollectionToArrayTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ArrayCollectionToArrayTransformer constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param mixed $value
     *
     * @return array
     */
    public function transform($value): array
    {
        return !empty($value) ? $value->toArray() : [];
    }

    /**
     * @param mixed $value
     *
     * @return ArrayCollection
     */
    public function reverseTransform($value): ArrayCollection
    {
        return new ArrayCollection($value);
    }
}
