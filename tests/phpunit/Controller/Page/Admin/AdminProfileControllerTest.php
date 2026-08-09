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
use Inachis\Repository\User\UserViewStateRepository;
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
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class AdminProfileControllerTest extends InachisControllerTestCase
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
        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
        );

        $form = $this->createForm(false, $request);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects($this->once())
            ->method('getRoleNames')
            ->with(25)
            ->willReturn([]);

        $paginator = $this->createMock(Paginator::class);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getFiltered')
            ->willReturn($paginator);

        $result = $this->controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $roleRepository,
            $this->createStub(UserBulkActionService::class),
            $userRepository,
            $this->createViewStateManager(),
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

        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [
                'disable' => '',
                'items' => [$itemId],
            ],
        );

        $form = $this->createForm(true, $request);

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

        $userBulkActionService = $this->createMock(
            UserBulkActionService::class,
        );

        $userBulkActionService->expects($this->once())
            ->method('apply')
            ->with('disable', [$itemId])
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
    public function testListEnableAction(): void
    {
        $itemId = '01J00000000000000000000000';

        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [
                'enable' => '',
                'items' => [$itemId],
            ],
        );

        $form = $this->createForm(true, $request);

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
                "Action 'enable' applied to 1 users.",
            );

        $userBulkActionService = $this->createMock(
            UserBulkActionService::class,
        );

        $userBulkActionService->expects($this->once())
            ->method('apply')
            ->with('enable', [$itemId])
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
    public function testListDeleteAction(): void
    {
        $itemId = '01J00000000000000000000000';

        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [
                'delete' => '',
                'items' => [$itemId],
            ],
        );

        $form = $this->createForm(true, $request);

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
        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
            [
                'delete' => '',
                'items' => ['01J00000000000000000000000'],
            ],
        );

        $form = $this->createForm(true, $request);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->method('getForm')
            ->willReturn($form);

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
    public function testListReturnsViewWhenNoItemsAreSubmitted(): void
    {
        $request = $this->createRequest(
            [
                'offset' => 50,
                'limit' => 25,
            ],
        );

        $form = $this->createForm(false, $request);

        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->controller->expects($this->once())
            ->method('createFormBuilder')
            ->willReturn($formBuilder);

        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->expects($this->once())
            ->method('getRoleNames')
            ->with(25)
            ->willReturn([]);

        $paginator = $this->createMock(Paginator::class);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getFiltered')
            ->willReturn($paginator);

        $result = $this->controller->list(
            $request,
            $this->createStub(CategoryRepository::class),
            $roleRepository,
            $this->createStub(UserBulkActionService::class),
            $userRepository,
            $this->createViewStateManager(),
        );

        $this->assertSame(
            'rendered:inadmin/page/admin/list.html.twig',
            $result->getContent(),
        );
    }

    /**
     * @throws Exception
     */
    public function testEditView(): void
    {
        $request = $this->createRequest(
            ['id' => 'test-user'],
            [],
            '/incp/admin/test-user',
        );

        $user = new User('test-user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test-user'])
            ->willReturn($user);

        $form = $this->createEditForm(false);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

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

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $imageTransformer->method('isHEICSupported')
            ->willReturn(false);

        $result = $this->controller->edit(
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
        $request = $this->createEditRequest();

        $user = new User('test-user');

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createEditForm(true, 'enableDisable', $button);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

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
        $request = $this->createEditRequest();

        $user = new User('test-user');

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createEditForm(true, 'delete', $button);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

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
     * @throws Exception
     */
    public function testEditDisableTotp(): void
    {
        $request = $this->createEditRequest();

        $user = new User('test-user');

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createEditForm(true, 'disableTotp', $button);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $totpManager = $this->createMock(TotpManager::class);
        $totpManager->expects($this->once())
            ->method('disable')
            ->with($user);

        $trustedDeviceManager = $this->createMock(
            TrustedDeviceManager::class,
        );
        $trustedDeviceManager->expects($this->once())
            ->method('removeAll')
            ->with($user);

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with(
                'success',
                'Two-Factor Authentication has been disabled',
            );

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with(
                'incp_admin_edit',
                ['id' => 'test-user'],
            )
            ->willReturn(new RedirectResponse('/incp/admin/test-user'));

        $result = $this->controller->edit(
            $request,
            $this->createStub(ImageTransformer::class),
            $this->createStub(RecoveryCodeManager::class),
            $totpManager,
            $trustedDeviceManager,
            $this->createStub(UserAccountEmailService::class),
            $this->createUserProtectionService(),
            $userRepository,
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );
    }

    /**
     * @throws Exception
     */
    public function testEditEnableTotpRedirectsToSetup(): void
    {
        $request = $this->createEditRequest();

        $user = new User('test-user');

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createEditForm(true, 'enableTotp', $button);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_admin_totp_setup')
            ->willReturn(new RedirectResponse('/incp/admin/totp/setup'));

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
    }

    /**
     * @throws Exception
     */
    public function testEditRegenerateRecoveryCodes(): void
    {
        $request = $this->createEditRequest();

        $user = new User('test-user');

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(true);

        $form = $this->createEditForm(
            true,
            'regenerateCodes',
            $button,
        );

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        $recoveryCodeManager = $this->createMock(
            RecoveryCodeManager::class,
        );

        $codes = ['ABC123', 'DEF456'];

        $recoveryCodeManager->expects($this->once())
            ->method('generate')
            ->willReturn($codes);

        $this->controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('incp_security_recovery_codes_generate')
            ->willReturn(
                new RedirectResponse('/incp/security/recovery-codes'),
            );

        $result = $this->controller->edit(
            $request,
            $this->createStub(ImageTransformer::class),
            $recoveryCodeManager,
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

        $this->assertSame(
            $codes,
            $request->getSession()->get('recovery_codes'),
        );
    }

    /**
     * @throws Exception
     */
    public function testEditNewUserPersistsAndRegistersUser(): void
    {
        $request = $this->createRequest(
            ['id' => 'new'],
            [
                'user' => [
                    'timezone' => 'Europe/London',
                    'locale' => 'en',
                    'color' => '#099bdd',
                ],
            ],
        );
        $request->setMethod(Request::METHOD_POST);

        $button = $this->createMock(ClickableInterface::class);
        $button->method('isClicked')->willReturn(false);

        $form = $this->createEditForm(true);

        $this->controller->expects($this->once())
            ->method('createForm')
            ->willReturn($form);

        $userAccountEmailService = $this->createMock(
            UserAccountEmailService::class,
        );

        $userAccountEmailService->expects($this->once())
            ->method('registerNewUser')
            ->with(
                $this->isInstanceOf(User::class),
                $this->arrayHasKey('viewModel'),
                $this->isInstanceOf(\Closure::class),
            );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'User details saved.');

        $this->controller->method('generateUrl')
            ->willReturn('/incp/admin/new');

        $this->controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/incp/admin/new'));

        $result = $this->controller->edit(
            $request,
            $this->createStub(ImageTransformer::class),
            $this->createStub(RecoveryCodeManager::class),
            $this->createStub(TotpManager::class),
            $this->createStub(TrustedDeviceManager::class),
            $userAccountEmailService,
            $this->createUserProtectionService(),
            $this->createStub(UserRepository::class),
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $result,
        );
    }

    /**
     * Creates a request with a session attached.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $requestData
     */
    private function createRequest(
        array $attributes = [],
        array $requestData = [],
        string $uri = '/incp/admin/list/25/50',
    ): Request {
        $request = new Request(
            [],
            $requestData,
            $attributes,
            [],
            [],
            [
                'REQUEST_URI' => $uri,
            ],
        );

        $request->setSession(
            new Session(new MockArraySessionStorage()),
        );

        return $request;
    }

    /**
     * Creates a POST request for editing a user.
     *
     * @throws \Symfony\Component\HttpFoundation\Exception\SessionNotFoundException
     */
    private function createEditRequest(): Request
    {
        $request = $this->createRequest(
            ['id' => 'test-user'],
            [
                'user' => [
                    'username' => 'test-user',
                    'displayName' => 'Test user',
                    'email' => 'test-user@example.com',
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'color' => '#099bdd',
                ],
            ],
            '/incp/admin/test-user',
        );

        $request->setMethod(Request::METHOD_POST);

        return $request;
    }

    /**
     * @throws Exception
     */
    private function createForm(
        bool $submitted,
        Request $request,
    ): Form&MockObject {
        $form = $this->createMock(Form::class);

        $form->method('handleRequest')
            ->with($request)
            ->willReturnSelf();

        $form->method('isSubmitted')
            ->willReturn($submitted);

        $form->method('createView')
            ->willReturn(new FormView());

        return $form;
    }

    /**
     * Creates the edit form mock.
     *
     * @throws Exception
     */
    private function createEditForm(
        bool $submitted,
        ?string $clickedButton = null,
        ?ClickableInterface $button = null,
    ): Form&MockObject {
        $form = $this->createMock(Form::class);

        $form->method('handleRequest')
            ->willReturnSelf();

        $form->method('isSubmitted')
            ->willReturn($submitted);

        $form->method('isValid')
            ->willReturn(true);

        $form->method('createView')
            ->willReturn(new FormView());

        $form->method('has')
            ->willReturnCallback(
                static function (string $name) use ($clickedButton): bool {
                    return null !== $clickedButton && $name === $clickedButton;
                },
            );

        if (null !== $clickedButton && null !== $button) {
            $form->method('get')
                ->with($clickedButton)
                ->willReturn($button);
        }

        return $form;
    }

    /**
     * Creates the ViewStateManager with its real constructor dependencies.
     *
     * @throws Exception
     */
    private function createViewStateManager(): ViewStateManager
    {
        return new ViewStateManager(
            $this->createStub(Security::class),
            $this->createStub(UserViewStateRepository::class),
        );
    }

    /**
     * Creates the controller with the framework methods used by these tests
     * mocked.
     *
     * getCurrentUser() is mocked because Symfony's AbstractController
     * implementation requires a fully initialised service container.
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
                'getCurrentUser',
                'redirect',
                'redirectToRoute',
                'render',
            ])
            ->getMock();

        $controller->method('getCurrentUser')
            ->willReturn(new User('current-user'));

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
     * UserProtectionService is final and these tests exercise paths where
     * its administrator-protection methods are not reached.
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
