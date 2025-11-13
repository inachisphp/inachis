<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Entity\Image;
use App\Form\ResourceType;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResourceTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
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
