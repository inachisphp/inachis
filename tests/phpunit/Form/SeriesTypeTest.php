<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Form;

use App\Entity\Series;
use App\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use App\Form\SeriesType;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeriesTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $transformer = $this->createMock(ArrayCollectionToArrayTransformer::class);
        return [
            new PreloadedExtension([new SeriesType($translator, $router, $transformer)], [])
        ];
    }

    public function testConfigureOptionsSetsDataClass(): void
    {
        $form = $this->factory->create(SeriesType::class, new Series());
        $options = $form->getConfig()->getOptions();

        $this->assertArrayHasKey('data_class', $options);
        $this->assertSame(Series::class, $options['data_class']);
    }

    public function testBuildFormForNewSeries(): void
    {
        $form = $this->factory->create(SeriesType::class, new Series());
        $view = $form->createView();

        $expectedFields = [ 'title', 'subTitle', 'url', 'description', 'submit' ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }

    public function testBuildFormForExistingSeries(): void
    {
        $series = (new Series())->setId(Uuid::uuid1());
        $form = $this->factory->create(SeriesType::class, $series);
        $view = $form->createView();

        $expectedFields = [
            'title', 'subTitle', 'url', 'description', 'firstDate', 'lastDate', 'addItem', 'visibility',
            'submit', 'delete', 'remove',
        ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
