<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Inachis\Controller\Page\Admin\AdminProfileController;
use Inachis\Entity\Security\Role;
use Inachis\Entity\User\User;
use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use Inachis\Form\UserType;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Repository\User\UserViewStateRepository;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserProtectionServiceInterface;
use Inachis\Tests\phpunit\Helper\InachisControllerTestCase;
use Inachis\Transformer\ImageTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[CoversClass(AdminProfileController::class)]
final class AdminProfileControllerTest extends InachisControllerTestCase
{
    private AdminProfileController $controller;
    private FlashBagAwareSessionInterface $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new AdminProfileController(
            $this->entityManager,
            $this->params,
            $this->security,
            $this->translator,
            $this->wasteRepository,
            $this->pageViewFactory,
            $this->requestStack,
        );

        $this->session = $this->createStub(FlashBagAwareSessionInterface::class);
        $flashBag = $this->createStub(FlashBagInterface::class);
        $this->session->method('getFlashBag')->willReturn($flashBag);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $token = $this->createStub(TokenInterface::class);
        $currentUser = new User('admin_user', 'password', 'admin@example.com');
        $token->method('getUser')->willReturn($currentUser);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = $this->createStub(ContainerInterface::class);
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/incp/admin/list');

        $twig = $this->createStub(Environment::class);
        $twig->method('render')->willReturn('<html>Rendered View</html>');

        $container->method('has')->willReturnCallback(
            static fn (string $id): bool => in_array($id, [
                'router',
                'twig',
                'request_stack',
                'session',
                'security.token_storage',
            ], true)
        );

        $container->method('get')->willReturnCallback(
            function (string $id) use ($router, $twig, $tokenStorage) {
                return match ($id) {
                    'router' => $router,
                    'twig' => $twig,
                    'request_stack' => $this->requestStack,
                    'session' => $this->session,
                    'security.token_storage' => $tokenStorage,
                    default => null,
                };
            }
        );

        $this->controller->setContainer($container);
    }

    private function prepareRequest(Request $request): void
    {
        $request->setSession($this->session);
        $this->requestStack->push($request);
    }

    #[Test]
    public function listReturnsRenderedViewOnGetRequest(): void
    {
        $request = new Request();
        $this->prepareRequest($request);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $roleRepository = $this->createStub(RoleRepository::class);
        $userBulkActionService = $this->createStub(UserBulkActionService::class);
        $userRepository = $this->createStub(UserRepository::class);
        $userViewStateRepository = $this->createStub(UserViewStateRepository::class);

        $viewStateManager = new ViewStateManager($this->security, $userViewStateRepository);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formBuilder = $this->createStub(FormBuilderInterface::class);
        $form = $this->createStub(FormInterface::class);

        $formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($this->createStub(FormView::class));

        $paginator = $this->createStub(Paginator::class);
        $userRepository->method('getFiltered')->willReturn($paginator);
        $roleRepository->method('getRoleNames')->willReturn([]);

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
            $formFactory,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function listExecutesBulkActionAndRedirectsOnPostRequest(): void
    {
        $request = new Request([], ['items' => ['user1', 'user2'], 'delete' => '1']);
        $request->setMethod('POST');
        $this->prepareRequest($request);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $roleRepository = $this->createStub(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createStub(UserRepository::class);
        $userViewStateRepository = $this->createStub(UserViewStateRepository::class);

        $viewStateManager = new ViewStateManager($this->security, $userViewStateRepository);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formBuilder = $this->createStub(FormBuilderInterface::class);
        $form = $this->createStub(FormInterface::class);

        $formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $userBulkActionService
            ->expects(self::once())
            ->method('apply')
            ->with('delete', ['user1', 'user2'])
            ->willReturn(2);

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
            $formFactory,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isRedirection());
    }

    #[Test]
    public function listHandlesCannotRemoveLastAdministratorExceptionOnBulkAction(): void
    {
        $request = new Request([], ['items' => ['user1'], 'delete' => '1']);
        $request->setMethod('POST');
        $this->prepareRequest($request);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $roleRepository = $this->createStub(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createStub(UserRepository::class);
        $userViewStateRepository = $this->createStub(UserViewStateRepository::class);

        $viewStateManager = new ViewStateManager($this->security, $userViewStateRepository);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formBuilder = $this->createStub(FormBuilderInterface::class);
        $form = $this->createStub(FormInterface::class);

        $formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $userBulkActionService
            ->method('apply')
            ->willThrowException(new CannotRemoveLastAdministratorException());

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
            $formFactory,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isRedirection());
    }

    #[Test]
    public function editRendersFormForExistingUserOnGetRequest(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'admin_user');
        $this->prepareRequest($request);

        $user = new User('admin_user', 'password', 'admin@example.com');
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createStub(UserProtectionServiceInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createStub(FormInterface::class);
        $formFactory->method('create')->with(UserType::class, $user, ['validation_groups' => ['']])->willReturn($form);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($this->createStub(FormView::class));

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $imageTransformer->method('isHEICSupported')->willReturn(true);

        $recoveryCodeManager = $this->createStub(RecoveryCodeManager::class);
        $recoveryCodeManager->method('getRemainingCount')->willReturn(5);

        $totpManager = $this->createStub(TotpManager::class);
        $trustedDeviceManager = $this->createStub(TrustedDeviceManager::class);
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $response = $this->controller->edit(
            $request,
            $formFactory,
            $imageTransformer,
            $recoveryCodeManager,
            $totpManager,
            $trustedDeviceManager,
            $userAccountEmailService,
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function editSavesUserAndPreferencesOnValidSubmission(): void
    {
        $request = new Request([], [
            'user' => [
                'timezone' => 'UTC',
                'locale' => 'en',
                'color' => '#123456',
            ],
        ]);
        $request->setMethod('POST');
        $request->attributes->set('id', 'admin_user');
        $this->prepareRequest($request);

        $user = new User('admin_user', 'password', 'admin@example.com');
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createStub(UserProtectionServiceInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createStub(FormInterface::class);
        $formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $recoveryCodeManager = $this->createStub(RecoveryCodeManager::class);
        $totpManager = $this->createStub(TotpManager::class);
        $trustedDeviceManager = $this->createStub(TrustedDeviceManager::class);
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $response = $this->controller->edit(
            $request,
            $formFactory,
            $imageTransformer,
            $recoveryCodeManager,
            $totpManager,
            $trustedDeviceManager,
            $userAccountEmailService,
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isRedirection());
    }

    #[Test]
    public function editTriggersDisableTotpActionWhenClicked(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('id', 'admin_user');
        $this->prepareRequest($request);

        $user = new User('admin_user', 'password', 'admin@example.com');
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createStub(UserProtectionServiceInterface::class);

        $button = $this->createStub(SubmitButton::class);
        $button->method('isClicked')->willReturn(true);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createStub(FormInterface::class);
        $formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('has')->willReturnCallback(static fn (string $name): bool => 'disableTotp' === $name);
        $form->method('get')->willReturnCallback(static fn (string $name): mixed => 'disableTotp' === $name ? $button : null);

        $totpManager = $this->createMock(TotpManager::class);
        $totpManager->expects(self::once())->method('disable')->with($user);

        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $trustedDeviceManager->expects(self::once())->method('removeAll')->with($user);

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $recoveryCodeManager = $this->createStub(RecoveryCodeManager::class);
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $response = $this->controller->edit(
            $request,
            $formFactory,
            $imageTransformer,
            $recoveryCodeManager,
            $totpManager,
            $trustedDeviceManager,
            $userAccountEmailService,
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertTrue($response->isRedirection());
    }

    #[Test]
    public function editAddsFormErrorWhenRemovingLastAdministratorRole(): void
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('id', 'admin_user');
        $this->prepareRequest($request);

        $adminRole = new Role();
        $adminRole->setName('Administrator');

        $user = new User('admin_user', 'password', 'admin@example.com');
        $user->addAssignedRole($adminRole);

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createStub(UserProtectionServiceInterface::class);
        $userProtectionService
            ->method('assertAdministratorCanBeRemoved')
            ->willThrowException(new CannotRemoveLastAdministratorException('Cannot remove the last administrator.'));

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createMock(FormInterface::class);
        $assignedRolesField = $this->createMock(FormInterface::class);

        $formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $form->method('handleRequest')->willReturnCallback(
            function () use ($user, $adminRole, $form): FormInterface {
                $user->removeAssignedRole($adminRole);

                return $form;
            }
        );

        $form->method('get')->with('assignedRoles')->willReturn($assignedRolesField);
        $assignedRolesField->expects(self::once())->method('addError');

        $imageTransformer = $this->createStub(ImageTransformer::class);
        $recoveryCodeManager = $this->createStub(RecoveryCodeManager::class);
        $totpManager = $this->createStub(TotpManager::class);
        $trustedDeviceManager = $this->createStub(TrustedDeviceManager::class);
        $userAccountEmailService = $this->createStub(UserAccountEmailService::class);

        $response = $this->controller->edit(
            $request,
            $formFactory,
            $imageTransformer,
            $recoveryCodeManager,
            $totpManager,
            $trustedDeviceManager,
            $userAccountEmailService,
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }
}
