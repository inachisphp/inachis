<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Series;

use Inachis\Controller\Page\Series\SeriesController;
use Inachis\Entity\Content\{Page, Series};
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Service\Series\SeriesBulkActionService;
use Inachis\Service\Waste\WasteManagerService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SeriesControllerTest extends InachisControllerTestCase
{
    /**
     * @throws Exception
     */
    public function testList(): void
    {
        $request = new Request([], [], [
            'offset' => '50',
            'limit' => '25',
        ], [], [], [
            'REQUEST_URI' => '/incc/series/list/50/25'
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->expects(self::once())
            ->method('process')
            ->willReturn([
                'filters' => [],
                'offset' => '50',
                'limit' => '25',
                'sort' => 'lastDate desc',
            ]);
        $seriesBulkActionService = $this->createStub(SeriesBulkActionService::class);
        $seriesRepository = $this->createStub(SeriesRepository::class);

        $result = $controller->list($request, $contentQueryParameters, $seriesBulkActionService, $seriesRepository);
        $this->assertEquals('rendered:inadmin/page/series/list.html.twig', $result->getContent());
    }


    /**
     * @throws Exception
     */
    public function testListDelete(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'items' => [
                $uuid->toString(),
            ],
            'public' => '',
        ], [
            'offset' => '50',
            'limit' => '25',
        ], [], [], [
            'REQUEST_URI' => '/incc/series/list/50/25'
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['addFlash', 'createFormBuilder', 'redirectToRoute'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('submit');
        $form->method('getClickedButton')->willReturn($button);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_series_list')
            ->willReturn(new RedirectResponse('/incc/series/list/50/25'));
        $contentQueryParameters = $this->createStub(ContentQueryParameters::class);
        $seriesBulkActionService = $this->createMock(SeriesBulkActionService::class);
        $seriesBulkActionService->expects($this->once())
            ->method('apply')->willReturn(1);
        $seriesRepository = $this->createStub(SeriesRepository::class);

        $result = $controller->list($request, $contentQueryParameters, $seriesBulkActionService, $seriesRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/incc/series/list/50/25', $result->headers->get('Location'));
    }

    /**
     * @throws Exception
     */
    public function testEditExisting(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [], [
            'id' => $uuid->toString(),
        ], [], [], [
            'REQUEST_URI' => '/incc/series/edit/' . $uuid->toString(),
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $series = (new Series())->setId($uuid)->setTitle('test series');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())
            ->method('findOneBy')->willReturn($series);
        $imageRepository = $this->createStub(ImageRepository::class);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository, $this->createStub(WasteManagerService::class));
        $this->assertEquals('rendered:inadmin/page/series/edit.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testEditSaveNewSeries(): void
    {
        $request = new Request([], [
            'series' => [
                'image' => Uuid::uuid1()->toString(),
                'title' => 'test series',
                'url' => '',
            ],
        ], [
            'id' => null,
        ], [], [], [
            'REQUEST_URI' => '/incc/series/new',
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['addFlash', 'createForm', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->expects($this->atLeastOnce())->method('getName')->willReturn('submit');
        $form->expects($this->atLeastOnce())->method('getClickedButton')->willReturn($button);

        $controller->expects($this->once())->method('createForm')->willReturn($form);
        $controller->expects($this->once())->method('getUser')->willReturn(new User());

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn(null);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->method('findOneBy')->willReturn(new Image());
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository, $this->createStub(WasteManagerService::class));
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testEditDeleteSeries(): void
    {
        $request = new Request([],
            [
                'series' => [
                    'image' => Uuid::uuid1()->toString(),
                    'title' => 'test series',
                    'url' => '',
                'delete' => '',
            ],
        ], [
            'id' => null,
        ], [], [], [
            'REQUEST_URI' => '/incc/series/new',
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->expects($this->atLeastOnce())->method('getName')->willReturn('delete');
        $form->method('getClickedButton')->willReturn($button);

        $controller->expects($this->once())->method('createForm')->willReturn($form);
        $seriesRepository = $this->createStub(SeriesRepository::class);
        $imageRepository = $this->createStub(ImageRepository::class);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository, $this->createStub(WasteManagerService::class));
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testEditRemoveContentFromSeries(): void
    {
        $request = new Request([],
            [
                'series' => [
                    'image' => Uuid::uuid1()->toString(),
                    'title' => 'test series',
                    'url' => '',
                    'itemList' => [

                    ],
                    'remove' => '',
                ],
            ], [
                'id' => null,
            ], [], [], [
                'REQUEST_URI' => '/incc/series/new',
            ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->expects($this->atLeastOnce())->method('getName')->willReturn('remove');
        $form->expects($this->atLeastOnce())->method('getClickedButton')->willReturn($button);

        $controller->expects($this->once())->method('createForm')->willReturn($form);
        $controller->expects($this->once())->method('getUser')->willReturn(new User());

        $page = new Page();
        $series = new Series();

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn($series);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->method('findOneBy')->willReturn(new Image());
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('findBy')->willReturn([$page]);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository, $this->createStub(WasteManagerService::class));
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testContents(): void
    {
        $request = new Request([], [], [
            'id' => Uuid::uuid1(),
        ], [], [], [
            'REQUEST_URI' => '/incc/series/contents/test',
        ]);
        $seriesRepository = $this->createStub(SeriesRepository::class);
        $form = $this->createStub(Form::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository
            ])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $controller->method('createForm')->willReturn($form);
        $result = $controller->contents($request, $seriesRepository);
        $this->assertEquals('rendered:inadmin/partials/series_contents.html.twig', $result->getContent());
    }
}
