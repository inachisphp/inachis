<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Twig;

use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class PropertyExtension extends AbstractExtension
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('property', $this->property(...)),
        ];
    }

    public function property(mixed $object, ?string $path): mixed
    {
        if (!$path) {
            return $object;
        }

        try {
            return $this->propertyAccessor->getValue($object, $path);
        } catch (NoSuchPropertyException|AccessException) {
            return null;
        }
    }
}
