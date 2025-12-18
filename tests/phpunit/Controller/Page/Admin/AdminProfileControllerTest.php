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
use App\Service\User\UserAccountEmailService;
use App\Transformer\ImageTransformer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
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
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
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
        $this->controller->expects($this->atLeast(0))
            ->method('render')
            ->willReturnCallback(function (string $template, array $data) {
                return new Response('rendered:' . $template);
            });
        $formBuilder = $this->createStub(FormBuilder::class);
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
        $userBulkActionService = $this->createStub(UserBulkActionService::class);
        $userRepository = $this->createStub(UserRepository::class);
        $contentQueryParameters = $this->createMock(ContentQueryParameters::class);
        $contentQueryParameters->expects($this->once())->method('process')->willReturn([
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
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userBulkActionService->expects($this->once())->method('apply')->willReturn(1);
        $userRepository = $this->createStub(UserRepository::class);
        $contentQueryParameters = $this->createStub(ContentQueryParameters::class);
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
        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incc_admin_list')
            ->willReturn(new RedirectResponse('/incc/admin/list/50/25'));
        $formBuilder = $this->createMock(FormBuilder::class);
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $formBuilder->expects($this->once())->method('getForm')->willReturn($form);
        $this->controller->expects($this->once())->method('createFormBuilder')->willReturn($formBuilder);

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
        $imageTransformer = $this->createStub(ImageTransformer::class);
        $userRegistrationService = $this->createStub(UserAccountEmailService::class);
        $user = new User();
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->atLeastOnce())->method('findOneBy')->willReturn($user);

        $form = $this->createStub(Form::class);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

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

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $userRegistrationService = $this->createStub(UserAccountEmailService::class);
        $user = (new User())->setEmail('test-user@example.com');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->atLeast(0))->method('findOneBy')->willReturn($user);

        $button = $this->createMock(Button::class);
        $button->expects($this->atLeastOnce())->method('getName')->willReturn('enableDisable');

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->atLeastOnce())->method('getClickedButton')->willReturn($button);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

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

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $userRegistrationService = $this->createStub(UserAccountEmailService::class);
        $user = (new User())->setEmail('test-user@example.com');
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())->method('findOneBy')->willReturn($user);

        $button = $this->createMock(Button::class);
        $button->expects($this->atLeastOnce())->method('getName')->willReturn('delete');

        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('isSubmitted')->willReturn(true);
        $form->expects($this->once())->method('isValid')->willReturn(true);
        $form->expects($this->atLeastOnce())->method('getClickedButton')->willReturn($button);
        $this->controller->expects($this->once())->method('createForm')->willReturn($form);

        $result = $this->controller->edit(
            $request,
            $imageTransformer,
            $userRegistrationService,
            $userRepository
        );
        $this->assertInstanceOf(RedirectResponse::class, $result);
    }
}
