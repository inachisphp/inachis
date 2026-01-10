<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Form;

use Inachis\Entity\Series;
use Inachis\Form\DataTransformer\ArrayCollectionToArrayTransformer;
use Inachis\Form\SeriesType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class SeriesTypeTest extends TypeTestCase
{

    protected function getExtensions(): array
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $router = $this->createStub(RouterInterface::class);
        $transformer = $this->createStub(ArrayCollectionToArrayTransformer::class);
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
            'title', 'subTitle', 'url', 'description', 'firstDate', 'lastDate',
            'bulkCreate', 'addItem', 'visibility','submit', 'delete', 'remove',
        ];
        $this->assertSame($expectedFields, array_keys($view->children));
    }
}
