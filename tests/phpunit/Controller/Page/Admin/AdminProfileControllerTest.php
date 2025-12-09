<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Controller\Page\Admin;

use App\Controller\Page\Admin\AdminProfileController;
use App\Entity\User;
use App\Model\ContentQueryParameters;
use App\Repository\UserRepository;
use App\Service\User\UserBulkActionService;
use App\Service\User\UserRegistrationService;
use App\Transformer\ImageTransformer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use Ramsey\Uuid\Uuid;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Button;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminProfileControllerTest extends WebTestCase
{
    /**
     * @var AdminProfileController&MockObject $controller
     */
    protected AdminProfileController $controller;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->controller = $this->getMockBuilder(AdminProfileController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'createFormBuilder',
                'generateUrl',
                'redirect',
                'redirectToRoute',
                'render',
            ])
            ->getMock();
        $this->controller->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $formBuilder = $this->createMock(FormBuilder::class);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

        parent::setUp();
    }

    /**
     * @throws Exception
     */
    public function testList(): void
    {
        $request = new Request([], [], [
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/list/50/25'
        ]);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->method('process')->willReturn([
            'filters' => [],
            'offset' => 50,
            'limit' => 25,
        ]);
        $result = $this->controller->list($request, $contentQueryParameters, $userBulkActionService, $userRepository);
        $this->assertEquals('rendered:inadmin/page/admin/list.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     */
    public function testListDisableAction(): void
    {
        $request = new Request([], [
            'disable' => '',
            'items' => [
                Uuid::uuid1()->toString(),
            ],
        ], [
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/list/50/25'
        ]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userBulkActionService->method('apply')->willReturn(1);
        $userRepository = $this->createMock(UserRepository::class);
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
                $this->controller = $this->getMockBuilder(AdminProfileController::class)
            ->setConstructorArgs([$entityManager, $security, $translator])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'createFormBuilder',
                'generateUrl',
                'redirect',
                'redirectToRoute',
                'render',
            ])
            ->getMock();
        $this->controller
            ->method('redirectToRoute')
            ->with('incc_admin_list')
            ->willReturn(new RedirectResponse('/incc/admin/list/50/25'));
        $formBuilder = $this->createMock(FormBuilder::class);
        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $formBuilder->method('getForm')->willReturn($form);
        $this->controller->method('createFormBuilder')->willReturn($formBuilder);

        $result = $this->controller->list($request, $contentQueryParameters, $userBulkActionService, $userRepository);
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testEditView(): void
    {
        $request = new Request([], [], [
            'id' => 'test-user',
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/test-user'
        ]);
        $imageTransformer = $this->createMock(ImageTransformer::class);
        $userRegistrationService = $this->createMock(UserRegistrationService::class);
        $user = new User();
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(Form::class);
        $this->controller->method('createForm')->willReturn($form);

        $result = $this->controller->edit(
            $request,
            $imageTransformer,
            $userRegistrationService,
            $userRepository
        );
        $this->assertEquals('rendered:inadmin/page/admin/profile.html.twig', $result->getContent());
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testEditSaveEnableDisable(): void
    {
        $formData = [
            'user' => [
                'username' => 'test-user',
                'displayName' => 'Test user',
                'email' => 'test-user@example.com',
                'timezone' => 'UTC',
            ],
        ];
        $request = new Request([], [ $formData, ], [
            'id' => 'new',
        ], [], [], [
            'REQUEST_URI' => '/incc/admin/test-user'
        ]);
        $request->setMethod(Request::METHOD_POST);

        $imageTransformer = $this->createMock(ImageTransformer::class);
        $userRegistrationService = $this->createMock(UserRegistrationService::class);
        $user = (new User())->setEmail('test-user@example.com');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('enableDisable');

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getClickedButton')->willReturn($button);
        $this->controller->method('createForm')->willReturn($form);

        $result = $this->controller->edit(
            $request,
            $imageTransformer,
            $userRegistrationService,
            $userRepository
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    /**
     * @throws Exception
     * @throws RandomException
     */
    public function testEditDelete(): void
    {
        $formData = [
            'user' => [
                'username' => 'test-user',
                'displayName' => 'Test user',
                'email' => 'test-user@example.com',
                'timezone' => 'UTC',
            ],
        ];
        $request = new Request([], [ $formData, ], [], [], [], [
            'REQUEST_URI' => '/incc/admin/test-user'
        ]);
        $request->setMethod(Request::METHOD_POST);

        $imageTransformer = $this->createMock(ImageTransformer::class);
        $userRegistrationService = $this->createMock(UserRegistrationService::class);
        $user = (new User())->setEmail('test-user@example.com');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $button = $this->createMock(Button::class);
        $button->method('getName')->willReturn('delete');

        $form = $this->createMock(Form::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getClickedButton')->willReturn($button);
        $this->controller->method('createForm')->willReturn($form);

        $result = $this->controller->edit(
            $request,
            $imageTransformer,
            $userRegistrationService,
            $userRepository
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}
