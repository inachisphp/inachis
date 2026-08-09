<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Controller\Page\Admin\AdminProfileController;
use Inachis\Entity\User\User;
use Inachis\Model\ContentQueryParameters;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\User\ProfileColorPalette;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserProtectionService;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Inachis\Transformer\ImageTransformer;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class AdminProfileControllerTest extends InachisControllerTestCase
{
    /**
     * @var AdminProfileController&MockObject
     */
    protected AdminProfileController $controller;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->createController();
    }

    /**
     * @throws Exception
     */
    public function testList(): void
    {
        $request = new Request(
            [], [],
            [
                'offset' => 50,
                'limit' => 25,
            ], [], [],
            [
                'REQUEST_URI' => '/incp/admin/list/25/50',
            ],
        );
        $request->setSession(new Session(new MockArraySessionStorage()));

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturnSelf();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(false);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn(new \Symfony\Component\Form\FormView());

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects($this->once())
            ->method('getRoleNames')
            ->with(25)
            ->willReturn([]);

        $userBulkActionService = $this->createStub(
            UserBulkActionService::class,
        );

        $userRepository = $this->createMock(UserRepository::class);

        $paginator = $this->createMock(Paginator::class);

        $userRepository->expects($this->once())
            ->method('getFiltered')
            ->willReturn($paginator);

        $viewStateManager = $this->createViewStateManager();

        $result = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
        );

        $this->assertSame(
            'rendered:inadmin/page/admin/list.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testListDisableAction(): void
    {
        $itemId = '01J00000000000000000000000';

        $request = new Request(
            [],
            [
                'disable' => '',
                'items' => [$itemId],
            ],
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [],
            [],
            [
                'REQUEST_URI' => '/incp/admin/list/25/50',
            ],
        );

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturnSelf();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_list')
            ->willReturn(new RedirectResponse('/incp/admin/list'));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'success',
                "Action 'disable' applied to 1 users.",
            );

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $roleRepository = $this->createStub(RoleRepository::class);

        $userBulkActionService = $this->createMock(
            UserBulkActionService::class,
        );
        $userBulkActionService->expects($this->once())
            ->method('apply')
            ->with('disable', [$itemId])
            ->willReturn(1);

        $userRepository = $this->createStub(UserRepository::class);

        $result = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $this->createViewStateManager(),
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testListDeleteAction(): void
    {
        $itemId = '01J00000000000000000000000';

        $request = new Request(
            [],
            [
                'delete' => '',
                'items' => [$itemId],
            ],
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [],
            [],
            [
                'REQUEST_URI' => '/incp/admin/list/25/50',
            ],
        );

        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturnSelf();
        $form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_list')
            ->willReturn(new RedirectResponse('/incp/admin/list'));

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'success',
                "Action 'delete' applied to 1 users.",
            );

        $userBulkActionService = $this->createMock(
            UserBulkActionService::class,
        );
        $userBulkActionService->expects($this->once())
            ->method('apply')
            ->with('delete', [$itemId])
            ->willReturn(1);

        $result = $this->controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $this->createStub(RoleRepository::class),
            $userBulkActionService,
            $this->createStub(UserRepository::class),
            $this->createViewStateManager(),
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testListHandlesLastAdministratorException(): void
    {
        $request = new Request(
            [],
            [
                'delete' => '',
                'items' => ['01J00000000000000000000000'],
            ],
            [
                'offset' => 50,
                'limit' => 25,
            ],
        );

        $form = $this->createMock(Form::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')->willReturn($form);

        $this->controller->method('createFormBuilder')
            ->willReturn($formBuilder);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'error',
                $this->isString(),
            );

        $this->controller->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/incp/admin/list'));

        $userBulkActionService = $this->createMock(
            UserBulkActionService::class,
        );

        $userBulkActionService->expects($this->once())
            ->method('apply')
            ->willThrowException(
                new \Inachis\Exception\User\CannotRemoveLastAdministratorException(),
            );

        $result = $this->controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $this->createStub(RoleRepository::class),
            $userBulkActionService,
            $this->createStub(UserRepository::class),
            $this->createViewStateManager(),
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testEditView(): void
    {
        $request = new Request(
            [],
            [],
            [
                'id' => 'test-user',
            ],
            [],
            [],
            [
                'REQUEST_URI' => '/incp/admin/test-user',
            ],
        );

        $user = new User('test-user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test-user'])
            ->willReturn($user);

        $form = $this->createMock(Form::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')
            ->willReturn(new \Symfony\Component\Form\FormView());

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller->method('render')
            ->willReturnCallback(
                static function (
                    string $template,
                    array $data,
                ): Response {
                    return new Response('rendered:'.$template);
                },
            );

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $recoveryCodeManager = $this->createMock(
            RecoveryCodeManager::class,
        );
        $recoveryCodeManager->expects($this->once())
            ->method('getRemainingCount')
            ->with($user)
            ->willReturn(0);

        $trustedDeviceManager = $this->createMock(
            TrustedDeviceManager::class,
        );
        $trustedDeviceManager->expects($this->once())
            ->method('getCurrentTrustedDevice')
            ->with($user, $request)
            ->willReturn(null);
        $trustedDeviceManager->expects($this->once())
            ->method('getTrustedDevices')
            ->with($user)
            ->willReturn([]);

        $controller = $this->getMockBuilder(AdminProfileController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
            ->onlyMethods([
                'addFlash',
                'createForm',
                'render',
            ])
            ->getMock();

        $controller->method('render')
            ->willReturnCallback(
                static function (
                    string $template,
                    array $data,
                ): Response {
                    return new Response('rendered:'.$template);
                },
            );

        $controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $imageTransformer->method('isHEICSupported')->willReturn(false);

        $result = $controller->edit(
            $request,
            $imageTransformer,
            $recoveryCodeManager,
            $this->createStub(TotpManager::class),
            $trustedDeviceManager,
            $this->createStub(UserAccountEmailService::class),
            $this->createUserProtectionService(),
            $userRepository,
        );

        $this->assertSame(
            'rendered:inadmin/page/admin/profile.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testEditSaveEnableDisable(): void
    {
        $request = new Request(
            [],
            [
                'user' => [
                    'username' => 'test-user',
                    'displayName' => 'Test user',
                    'email' => 'test-user@example.com',
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'color' => ProfileColorPalette::DEFAULT_COLOR,
                ],
            ],
            [
                'id' => 'test-user',
            ],
        );
        $request->setMethod(Request::METHOD_POST);

        $user = new User('test-user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createMock(Form::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('has')
            ->willReturnCallback(
                static fn (string $name): bool => 'enableDisable' === $name,
            );
        $form->method('get')
            ->with('enableDisable')
            ->willReturn($button);
        $form->method('createView')
            ->willReturn(new \Symfony\Component\Form\FormView());

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'User details saved.');

        $this->controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->controller->method('generateUrl')
            ->willReturn('/incp/admin/test-user');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->controller->edit(
            $request,
            $this->createStub(ImageTransformer::class),
            $this->createStub(RecoveryCodeManager::class),
            $this->createStub(TotpManager::class),
            $this->createStub(TrustedDeviceManager::class),
            $this->createStub(UserAccountEmailService::class),
            $this->createUserProtectionService(),
            $userRepository,
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );

        $this->assertFalse($user->isEnabled());
    }

    /**
     * @throws Exception
     */
    public function testEditDelete(): void
    {
        $request = new Request(
            [],
            [
                'user' => [
                    'username' => 'test-user',
                    'displayName' => 'Test user',
                    'email' => 'test-user@example.com',
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'color' => ProfileColorPalette::DEFAULT_COLOR,
                ],
            ],
            [
                'id' => 'test-user',
            ],
        );
        $request->setMethod(Request::METHOD_POST);

        $user = new User('test-user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createMock(Form::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('has')
            ->willReturnCallback(
                static fn (string $name): bool => 'delete' === $name,
            );
        $form->method('get')
            ->with('delete')
            ->willReturn($button);
        $form->method('createView')
            ->willReturn(new \Symfony\Component\Form\FormView());

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'User details saved.');

        $this->controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $this->controller->method('generateUrl')
            ->willReturn('/incp/admin/test-user');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->controller->edit(
            $request,
            $this->createStub(ImageTransformer::class),
            $this->createStub(RecoveryCodeManager::class),
            $this->createStub(TotpManager::class),
            $this->createStub(TrustedDeviceManager::class),
            $this->createStub(UserAccountEmailService::class),
            $this->createUserProtectionService(),
            $userRepository,
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );

        $this->assertTrue($user->isRemoved());
    }

    /**
     * Creates the controller with the methods used by these tests mocked.
     *
     * @throws Exception
     */
    private function createController(): AdminProfileController&MockObject
    {
        /** @var AdminProfileController&MockObject $controller */
        $controller = $this->getMockBuilder(AdminProfileController::class)
            ->setConstructorArgs([
                $this->entityManager,
                $this->params,
                $this->security,
                $this->translator,
                $this->wasteRepository,
                $this->pageViewFactory,
                $this->requestStack,
            ])
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

        $controller->method('render')
            ->willReturnCallback(
                static function (
                    string $template,
                    array $data,
                ): Response {
                    return new Response('rendered:'.$template);
                },
            );

        return $controller;
    }

    /**
     * ViewStateManager is final and the redirect branches never use it.
     *
     * @throws \ReflectionException
     */
    private function createViewStateManager(): ViewStateManager
    {
        $reflection = new \ReflectionClass(ViewStateManager::class);

        /** @var ViewStateManager $manager */
        $manager = $reflection->newInstanceWithoutConstructor();

        return $manager;
    }

    /**
     * UserProtectionService is final and these tests deliberately exercise
     * non-administrator users, so its methods are never reached.
     *
     * @throws \ReflectionException
     */
    private function createUserProtectionService(): UserProtectionService
    {
        $reflection = new \ReflectionClass(UserProtectionService::class);

        /** @var UserProtectionService $service */
        $service = $reflection->newInstanceWithoutConstructor();

        return $service;
    }
}
