<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Twig;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Used to simplify Twig accessing object/array propteries such as
 * object.linkedObject.property.
 */
final class PropertyExtension extends AbstractExtension
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    /**
     * Retuns the functions this will make available to Twig.
     *
     * @return list<TwigFunction> Provides access to property callable function
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('property', $this->property(...)),
        ];
    }

    /**
     * Returns the value at the given property path.
     *
     * @param object|array<mixed> $object object or array to read from
     * @param string|null         $path   Property path. If null or empty, the
     *                                    original value is returned.
     *
     * @return mixed returns the resolved value, the original value if no path
     *               is given, or null if the property cannot be accessed
     */
    public function property(mixed $object, ?string $path): mixed
    {
        if (null === $path || '' === $path) {
            return $object;
        }

        try {
            return $this->propertyAccessor->getValue($object, $path);
        } catch (NoSuchPropertyException|AccessException) {
            return null;
        }
    }
}
