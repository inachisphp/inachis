<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Twig;

use Inachis\Twig\PropertyExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Twig\TwigFunction;

class PropertyExtensionTest extends TestCase
{
    public function testGetFunctionsReturnsRegisteredTwigFunctions(): void
    {
        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $extension = new PropertyExtension($propertyAccessor);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('property', $functions[0]->getName());
    }

    public function testPropertyReturnsOriginalValueWhenPathIsNull(): void
    {
        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $extension = new PropertyExtension($propertyAccessor);

        $object = (object) ['title' => 'Test Item'];

        $this->assertSame($object, $extension->property($object, null));
    }

    public function testPropertyReturnsOriginalValueWhenPathIsEmpty(): void
    {
        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $extension = new PropertyExtension($propertyAccessor);

        $object = ['title' => 'Test Array'];

        $this->assertSame($object, $extension->property($object, ''));
    }

    public function testPropertyResolvesPathValueSuccessfully(): void
    {
        $object = (object) ['author' => (object) ['name' => 'John Doe']];

        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')->willReturnMap([
            [$object, 'author.name', 'John Doe'],
        ]);

        $extension = new PropertyExtension($propertyAccessor);

        $this->assertSame('John Doe', $extension->property($object, 'author.name'));
    }

    public function testPropertyReturnsNullWhenNoSuchPropertyExceptionIsThrown(): void
    {
        $object = (object) ['title' => 'Test Item'];

        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')->willThrowException(new NoSuchPropertyException());

        $extension = new PropertyExtension($propertyAccessor);

        $this->assertNull($extension->property($object, 'nonExistentField'));
    }

    public function testPropertyReturnsNullWhenAccessExceptionIsThrown(): void
    {
        $object = (object) ['title' => 'Test Item'];

        $propertyAccessor = $this->createStub(PropertyAccessorInterface::class);
        $propertyAccessor->method('getValue')->willThrowException(new AccessException());

        $extension = new PropertyExtension($propertyAccessor);

        $this->assertNull($extension->property($object, 'unreachableProperty'));
    }
}
