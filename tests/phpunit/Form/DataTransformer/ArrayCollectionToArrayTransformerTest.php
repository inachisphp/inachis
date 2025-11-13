<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form\DataTransformer;

use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ArrayCollectionToArrayTransformerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ArrayCollectionToArrayTransformer $transformer;

    public function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->transformer = new ArrayCollectionToArrayTransformer($this->em);
    }

    public function testTransformEmpty(): void
    {
        $this->assertEmpty($this->transformer->transform(''));
    }

    public function testTransformArrayCollection(): void
    {
        $result = $this->transformer->transform(new ArrayCollection(['something']));
        $this->assertNotEmpty($result);
        $this->assertContains('something', $result);
    }

    public function testReverseTransform(): void
    {
        $result = $this->transformer->reverseTransform(['something']);
        $this->assertContains('something', $result);
        $this->assertInstanceOf(ArrayCollection::class, $result);
    }
}
