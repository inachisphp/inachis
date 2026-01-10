<?php

/**
 * This file is part of the inachis framework
 * 
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller\Page\Search;

use Inachis\Controller\Page\Search\SearchController;
use Inachis\Entity\User;
use Inachis\Model\SearchResult;
use Inachis\Repository\SearchRepository;
use Inachis\Repository\UrlRepository;
use Inachis\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchControllerTest extends WebTestCase
{
    private SearchController $controller;
    private EntityManagerInterface|MockObject $entityManager;
    private Security|MockObject $security;

    private TranslatorInterface $translator;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(TranslatorInterface::class);
        $form = $this->createStub(Form::class);
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->atLeast(0))
            ->method('getForm')->willReturn($form);

        $this->controller = $this->getMockBuilder(SearchController::class)
            ->setConstructorArgs([$this->entityManager, $this->security, $this->translator])
            ->onlyMethods(['createFormBuilder', 'generateUrl', 'render'])
            ->getMock();
        $this->controller->expects($this->atLeast(0))
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $this->controller->expects($this->atLeast(0))
            ->method('generateUrl')
            ->willReturnCallback(function (string $route, array $parameters) {
                return 'redirected:' . $route;
            });
        $this->controller->expects($this->atLeast(0))
            ->method('createFormBuilder')->willReturn($formBuilder);
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function testResults(): void
    {
        $request = new Request([], [
            'sort' => 'title asc',
        ], [
            'keyword' => 'test',
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/search/results/{keyword}/{offset}/{limit}',
        ]);
        $request->setMethod(Request::METHOD_POST);
        $request->setSession(new Session(new MockArraySessionStorage()));

        $results = $this->createMock(SearchResult::class);
        $results->method('getResults')->willReturn([
            0 => [
                'type' => 'Image',
                'title' => 'Test image',
                'sub_title' => 'image.jpeg',
                'relevance' => '0.345678',
                'url' => '',
                'author' => '',
            ],
            1 => [
                'type' => 'Series',
                'title' => 'Test Series',
                'sub_title' => '',
                'id' => Uuid::uuid1(),
                'relevance' => '0.3',
                'url' => '',
                'author' => '',
            ],
            2 => [
                'type' => 'Post',
                'title' => 'Test Series',
                'sub_title' => '',
                'id' => Uuid::uuid1(),
                'relevance' => '0.3',
                'url' => '',
                'author' => '',
            ],
        ]);
        $results->expects($this->once())->method('getOffset')->willReturn(50);
        $results->expects($this->once())->method('getLimit')->willReturn(25);
        $results->expects($this->once())->method('getTotal')->willReturn(3);
        $searchRepository = $this->createMock(SearchRepository::class);
        $searchRepository->expects($this->once())->method('search')->willReturn($results);
        $urlRepository = $this->createStub(UrlRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->atLeastOnce())->method('findOneBy')->willReturn(new User());

        $result = $this->controller->results($request, $searchRepository, $urlRepository, $userRepository);
        $this->assertEquals('rendered:inadmin/page/search/results.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testResultsRedirectEmpty(): void
    {
        $request = new Request([], [
            'keyword' => 'test',
            'sort' => 'title asc',
        ], [
            'keyword' => ' ',
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/search/results/ /{offset}/{limit}',
        ]);

        $searchRepository = $this->createStub(SearchRepository::class);
        $urlRepository = $this->createStub(UrlRepository::class);
        $userRepository = $this->createStub(UserRepository::class);

        $result = $this->controller->results($request, $searchRepository, $urlRepository, $userRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('redirected:incc_search_results', $result->headers->get('Location'));
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function testResultsSortFromSession(): void
    {
        $request = new Request([], [], [
            'keyword' => 'test',
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/search/results/{keyword}/{offset}/{limit}',
        ]);
        $session = new Session(new MockArraySessionStorage());
        $session->set('search_sort', 'from session');
        $request->setSession($session);

        $results = $this->createMock(SearchResult::class);
        $results->expects($this->once())->method('getResults')->willReturn([
            0 => [
                'type' => 'Image',
                'title' => 'Test image',
                'sub_title' => 'image.jpeg',
                'relevance' => '0.345678',
                'url' => '',
                'author' => '',
            ],
        ]);
        $results->expects($this->once())->method('getOffset')->willReturn(50);
        $results->expects($this->once())->method('getLimit')->willReturn(25);
        $results->expects($this->once())->method('getTotal')->willReturn(1);
        $searchRepository = $this->createMock(SearchRepository::class);
        $searchRepository->expects($this->once())->method('search')->willReturn($results);
        $urlRepository = $this->createStub(UrlRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn(new User());

        $result = $this->controller->results($request, $searchRepository, $urlRepository, $userRepository);
        $this->assertEquals('rendered:inadmin/page/search/results.html.twig', $result->getContent());
    }
}
