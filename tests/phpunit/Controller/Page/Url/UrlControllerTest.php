<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Url;

use Inachis\Controller\Page\Url\UrlController;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\UrlRepository;
use Inachis\Service\Url\UrlBulkActionService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class UrlControllerTest extends WebTestCase
{
    /**
     * @throws Exception
     */
    public function testList(): void
    {
        $request = new Request([], [], [
            'offset' => '50',
            'limit' => '50',
        ], [], [], [
            'REQUEST_URI' => '/incc/url/list/50/25'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $controller = $this->getMockBuilder(UrlController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['createFormBuilder', 'render'])
            ->getMock();
        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->expects($this->once())
            ->method('process')
            ->willReturn([
                'filters' => [],
                'offset' => '50',
                'limit' => '25',
                'sort' => 'contentDate asc',
            ]);
        $urlBulkActionService = $this->createStub(UrlBulkActionService::class);
        $urlRepository = $this->createStub(UrlRepository::class);
        $result = $controller->list($request, $contentQueryParameters, $urlBulkActionService, $urlRepository);
        $this->assertEquals('rendered:inadmin/page/url/list.html.twig', $result->getContent());
    }


    /**
     * @throws Exception
     */
    public function testListMakeDefault(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'items' => [
                $uuid->toString(),
            ],
            'make_default' => '',
        ], [
            'offset' => '50',
            'limit' => '50',
        ], [], [], [
            'REQUEST_URI' => '/incc/url/list/50/25'
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $controller = $this->getMockBuilder(UrlController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods(['addFlash', 'createFormBuilder', 'redirectToRoute'])
            ->getMock();
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')->willReturn($form);
        $controller->expects($this->once())
            ->method('createFormBuilder')->willReturn($formBuilder);
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_url_list')
            ->willReturn(new RedirectResponse('/incc/url/list/50/25'));
        $contentQueryParameters = $this->createStub(ContentQueryParameters::class);
        $urlBulkActionService = $this->createStub(UrlBulkActionService::class);
        $urlBulkActionService->method('apply')->willReturn(2);
        $urlRepository = $this->createStub(UrlRepository::class);

        $result = $controller->list($request, $contentQueryParameters, $urlBulkActionService, $urlRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertSame('/incc/url/list/50/25', $result->headers->get('Location'));
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testCheckUrlUsage(): void
    {
        $uuid = Uuid::uuid1();
        $request = new Request([], [
            'id' => $uuid,
            'url' => 'test-url',
        ], [], [], [], [
            'REQUEST_URI' => '/incc/ax/check-url-usage'
        ]);
        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findSimilarUrlsExcludingId')->willReturn([
            [ 'link' => 'test-url' ],
        ]);
        $entityManager = $this->createStub(EntityManager::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $controller = new UrlController($entityManager, $security, $translator);
        $result = $controller->checkUrlUsage($request, $urlRepository);
        $this->assertEquals('test-url-1', $result->getContent());

        $urlRepository = $this->createMock(UrlRepository::class);
        $urlRepository->expects($this->once())
            ->method('findSimilarUrlsExcludingId')->willReturn([
            [ 'link' => 'test-url-3' ],
        ]);
        $result = $controller->checkUrlUsage($request, $urlRepository);
        $this->assertEquals('test-url-4', $result->getContent());
    }
}
