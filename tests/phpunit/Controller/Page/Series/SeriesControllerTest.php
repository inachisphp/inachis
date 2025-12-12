<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Series;

use App\Controller\Page\Series\SeriesController;
use App\Entity\Image;
use App\Entity\Page;
use App\Entity\Series;
use App\Entity\User;
use App\Model\ContentQueryParameters;
use App\Repository\ImageRepository;
use App\Repository\PageRepository;
use App\Repository\SeriesRepository;
use App\Service\Series\SeriesBulkActionService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class SeriesControllerTest extends WebTestCase
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->method('render')
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
        $seriesBulkActionService = $this->createMock(SeriesBulkActionService::class);
        $seriesRepository = $this->createMock(SeriesRepository::class);
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createFormBuilder', 'redirectToRoute'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('submit');
        $form->method('getClickedButton')->willReturn($button);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')->willReturn($form);
        $controller->method('createFormBuilder')->willReturn($formBuilder);
        $controller
            ->method('redirectToRoute')
            ->with('incc_series_list')
            ->willReturn(new RedirectResponse('/incc/series/list/50/25'));
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $seriesBulkActionService = $this->createMock(SeriesBulkActionService::class);
        $seriesBulkActionService->method('apply')->willReturn(1);
        $seriesRepository = $this->createMock(SeriesRepository::class);
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $series = (new Series())->setId($uuid)->setTitle('test series');
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn($series);
        $imageRepository = $this->createMock(ImageRepository::class);
        $pageRepository = $this->createMock(PageRepository::class);
        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository);
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('submit');
        $form->method('getClickedButton')->willReturn($button);

        $controller->method('createForm')->willReturn($form);
        $controller->method('getUser')->willReturn(new User());

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn(null);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->method('findOneBy')->willReturn(new Image());
        $pageRepository = $this->createMock(PageRepository::class);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository);
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('delete');
        $form->method('getClickedButton')->willReturn($button);

        $controller->method('createForm')->willReturn($form);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $imageRepository = $this->createMock(ImageRepository::class);
        $pageRepository = $this->createMock(PageRepository::class);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository);
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
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createForm', 'generateUrl', 'getUser', 'redirect'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('remove');
        $form->method('getClickedButton')->willReturn($button);

        $controller->method('createForm')->willReturn($form);
        $controller->method('getUser')->willReturn(new User());

        $page = new Page();
        $series = new Series();

        $seriesRepository = $this->createMock(SeriesRepository::class);
        $seriesRepository->method('findOneBy')->willReturn($series);
        $imageRepository = $this->createMock(ImageRepository::class);
        $imageRepository->method('findOneBy')->willReturn(new Image());
        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->method('findBy')->willReturn([$page]);

        $result = $controller->edit($request, $seriesRepository, $imageRepository, $pageRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function testContents(): void
    {
        $request = new Request([], [], [
            'id' => Uuid::uuid1(),
        ], [], [], [
            'REQUEST_URI' => '/incc/series/contents/test',
        ]);
        $entityManager = $this->createMock(EntityManager::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $seriesRepository = $this->createMock(SeriesRepository::class);
        $form = $this->createMock(Form::class);
        $controller = $this->getMockBuilder(SeriesController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createForm', 'render'])
            ->getMock();
        $controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $controller->method('createForm')->willReturn($form);
        $result = $controller->contents($request, $seriesRepository);
        $this->assertEquals('rendered:inadmin/partials/series_contents.html.twig', $result->getContent());
    }
}
