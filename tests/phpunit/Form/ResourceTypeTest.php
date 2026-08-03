<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Media\Image;
use Inachis\Form\ResourceType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class ResourceTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        return [
            new PreloadedExtension([new ResourceType($translator)], [])
        ];
    }

    public function testConfigureOptionsSetsDataClass(): void
    {
        $form = $this->factory->create(ResourceType::class, new Image());
        $options = $form->getConfig()->getOptions();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertSame(Image::class, $options['data_class']);
    }

    public function testBuildFormForImage(): void
    {
        $form = $this->factory->create(ResourceType::class, new Image());
        $view = $form->createView();

        $expectedFields = [ 'title', 'altText', 'description', 'generate_alt_text', 'submit', 'delete' ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
