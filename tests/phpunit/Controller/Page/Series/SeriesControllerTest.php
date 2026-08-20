<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Series;

use Inachis\Controller\Page\Series\SeriesController;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Inachis\Entity\Media\Image;
use Inachis\Entity\User\User;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Content\PageRepository;
use Inachis\Repository\Content\SeriesRepository;
use Inachis\Repository\Media\ImageRepository;
use Inachis\Repository\User\UserViewStateRepository;
use Inachis\Service\Content\Series\SeriesBulkActionService;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\Waste\WasteManagerService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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
            'REQUEST_URI' => '/incp/series/list/50/25',
        ]);
        $request->setSession(new Session(new MockArraySessionStorage()));
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });
        $seriesBulkActionService = $this->createStub(SeriesBulkActionService::class);
        $seriesRepository = $this->createStub(SeriesRepository::class);

        $result = $controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $seriesBulkActionService,
            $seriesRepository,
            new ViewStateManager(
                $this->createStub(Security::class),
                $this->createStub(UserViewStateRepository::class),
            ),
        );
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
            'REQUEST_URI' => '/incp/series/list/50/25',
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
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
            ->with('incp_series_list')
            ->willReturn(new RedirectResponse('/incp/series/list/50/25'));
        $contentQueryParameters = $this->createStub(ContentQueryParameters::class);
        $seriesBulkActionService = $this->createMock(SeriesBulkActionService::class);
        $seriesBulkActionService->expects($this->once())
            ->method('apply')->willReturn(1);
        $seriesRepository = $this->createStub(SeriesRepository::class);

        $result = $controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $seriesBulkActionService,
            $seriesRepository,
            new ViewStateManager(
                $this->createStub(Security::class),
                $this->createStub(UserViewStateRepository::class),
            ),
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/incp/series/list/50/25', $result->headers->get('Location'));
    }

    /**
     * @throws Exception
     */
    public function testEditExisting(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [], [
            'id' => $uuid->getBytes(),
        ], [], [], [
            'REQUEST_URI' => '/incp/series/edit/'.$uuid->toString(),
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });
        $series = (new Series())->setId($uuid)->setTitle('test series');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->expects($this->once())
            ->method('findOneBy')->willReturn($series);
        $imageRepository = $this->createStub(ImageRepository::class);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit(
            $request,
            $seriesRepository,
            $pageRepository,
            $this->createStub(WasteManagerService::class),
        );
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
                'image' => Uuid::uuid1()->getBytes(),
                'title' => 'test series',
                'url' => '',
            ],
        ], [], [], [], [
            'REQUEST_URI' => '/incp/series/new',
        ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'getCurrentUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);

        $controller->expects($this->once())->method('createForm')->willReturn($form);
        $controller->expects($this->once())->method('getCurrentUser')->willReturn(new User());
        $controller->expects($this->once())
            ->method('redirect')
            ->with($this->stringStartsWith('/incp/series/edit/'))
            ->willReturn(new RedirectResponse('/incp/series/edit/'));

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn(null);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->method('findOneBy')->willReturn(new Image());
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit(
            $request,
            $seriesRepository,
            $pageRepository,
            $this->createStub(WasteManagerService::class),
        );
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
                    'image' => Uuid::uuid1()->getBytes(),
                    'title' => 'test series',
                    'url' => '',
                    'delete' => '',
                ],
            ], [], [], [], [
                'REQUEST_URI' => '/incp/series/new',
            ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'getCurrentUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller->expects($this->once())->method('createForm')->willReturn($form);

        $controller->expects($this->once())
            ->method('redirect')
            ->with('/incp/series/edit//')
            ->willReturn(new RedirectResponse('/incp/series/edit/'));
        $seriesRepository = $this->createStub(SeriesRepository::class);
        $pageRepository = $this->createStub(PageRepository::class);

        $result = $controller->edit(
            $request,
            $seriesRepository,
            $pageRepository,
            $this->createStub(WasteManagerService::class),
        );
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
                    'image' => Uuid::uuid1()->getBytes(),
                    'title' => 'test series',
                    'url' => '',
                    'itemList' => [
                    ],
                    'remove' => '',
                ],
            ], [], [], [], [
                'REQUEST_URI' => '/incp/series/new',
            ]);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getCurrentUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMockForIntersectionOfInterfaces([
            FormInterface::class,
            ClickableInterface::class,
        ]);
        $button->expects($this->once())
            ->method('isClicked')
            ->willReturn(true);
        $form->method('has')
            ->willReturnCallback(
                static fn (string $name): bool => 'remove' === $name,
            );
        $form->expects($this->once())
            ->method('get')
            ->with('remove')
            ->willReturn($button);

        $controller->expects($this->once())->method('createForm')->willReturn($form);
        $controller->expects($this->once())->method('getCurrentUser')->willReturn(new User());

        $page = new Page();
        $series = new Series();

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findBy')->willReturn([$series]);
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->expects($this->once())->method('findBy')->willReturn([$page]);

        $result = $controller->edit(
            $request,
            $seriesRepository,
            $pageRepository,
            $this->createStub(WasteManagerService::class),
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testContents(): void
    {
        $request = new Request([], [], [
            'id' => Uuid::uuid1(),
        ], [], [], [
            'REQUEST_URI' => '/incp/series/contents/test',
        ]);
        $seriesRepository = $this->createStub(SeriesRepository::class);
        $form = $this->createStub(Form::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:'.$template);
            });
        $controller->method('createForm')->willReturn($form);
        $result = $controller->contents($request, $seriesRepository);
        $this->assertEquals('rendered:inadmin/partials/series_contents.html.twig', $result->getContent());
    }
}
