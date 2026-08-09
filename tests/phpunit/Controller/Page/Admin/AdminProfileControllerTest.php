<?php

declare(strict_types=1);

namespace Inachis\phpunit\Tests\Controller\Page\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Admin\AdminProfileController;
use Inachis\Entity\User\Role;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserPreference;
use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use Inachis\Form\UserType;
use Inachis\Model\Page\ViewModel;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserProtectionService;
use Inachis\Transformer\ImageTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class AdminProfileControllerTest extends TestCase
{
    private AdminProfileController $controller;
    private EntityManagerInterface&MockObject $entityManager;
    private ContainerInterface&MockObject $container;
    private FormFactoryInterface&MockObject $formFactory;
    private RouterInterface&MockObject $router;
    private Environment&MockObject $twig;
    private FlashBagAwareSessionInterface&MockObject $session;
    private FlashBagInterface&MockObject $flashBag;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->session = $this->createMock(FlashBagAwareSessionInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);

        $this->session->method('getFlashBag')->willReturn($this->flashBag);

        $this->container->method('has')->willReturnCallback(
            static fn (string $id): bool => in_array($id, ['form.factory', 'router', 'twig'], true)
        );

        $this->container->method('get')->willReturnCallback(
            fn (string $id) => match ($id) {
                'form.factory' => $this->formFactory,
                'router' => $this->router,
                'twig' => $this->twig,
                default => null,
            }
        );

        $this->controller = new class($this->entityManager) extends AdminProfileController {
            public ?User $currentUserMock = null;
       
            public function __construct(EntityManagerInterface $em)
            {
                $this->entityManager = $em;
                $this->viewModel = new ViewModel();
            }

            public function getCurrentUser(): ?User
            {
                return $this->currentUserMock;
            }
        };

        $this->controller->setContainer($this->container);
    }

    // =========================================================================
    // list() tests
    // =========================================================================

    #[Test]
    public function listRendersPageOnGetRequest(): void
    {
        $request = new Request();
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $roleRepository = $this->createMock(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $viewStateManager = $this->createMock(ViewStateManager::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);
        $formView = new FormView();

        $this->formFactory->expects(self::once())
            ->method('createBuilder')
            ->willReturn($formBuilder);
        $formBuilder->expects(self::once())->method('getForm')->willReturn($form);
        $form->expects(self::once())->method('handleRequest')->with($request);
        $form->expects(self::once())->method('isSubmitted')->willReturn(false);

        $params = ['sort' => 'displayName asc', 'view' => 'table'];
        $viewStateManager->expects(self::once())
            ->method('build')
            ->with($request, 'admin', self::isInstanceOf(ViewStateDefaults::class), $categoryRepository)
            ->willReturn($params);

        $userRepository->expects(self::once())
            ->method('getFiltered')
            ->with($params)
            ->willReturn([]);

        $roleRepository->expects(self::once())
            ->method('getRoleNames')
            ->with(25)
            ->willReturn(['Admin', 'User']);

        $form->expects(self::once())->method('createView')->willReturn($formView);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/admin/list.html.twig', self::callback(static function (array $context) use ($formView, $params): bool {
                return $context['dataset'] === []
                    && $context['form'] === $formView
                    && $context['query'] === $params
                    && $context['roles'] === ['Admin', 'User'];
            }))
            ->willReturn('<html>List</html>');

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
        );

        self::assertSame('<html>List</html>', $response->getContent());
    }

    #[Test]
    public function listHandlesBulkDeleteActionSuccessfully(): void
    {
        $request = new Request([], ['items' => ['user-1', 'user-2'], 'delete' => '1']);
        $request->setSession($this->session);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $roleRepository = $this->createMock(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $viewStateManager = $this->createMock(ViewStateManager::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $userBulkActionService->expects(self::once())
            ->method('apply')
            ->with('delete', ['user-1', 'user-2'])
            ->willReturn(2);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('success', "Action 'delete' applied to 2 users.");

        $this->router->expects(self::once())
            ->method('generate')
            ->with('incp_admin_list', [], RouterInterface::ABSOLUTE_PATH)
            ->willReturn('/incp/admin/list');

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/admin/list', $response->getTargetUrl());
    }

    #[Test]
    public function listHandlesBulkEnableAndDisableActions(): void
    {
        foreach (['enable', 'disable'] as $action) {
            $request = new Request([], ['items' => ['user-1'], $action => '1']);
            $request->setSession($this->session);

            $categoryRepository = $this->createMock(CategoryRepository::class);
            $roleRepository = $this->createMock(RoleRepository::class);
            $userBulkActionService = $this->createMock(UserBulkActionService::class);
            $userRepository = $this->createMock(UserRepository::class);
            $viewStateManager = $this->createMock(ViewStateManager::class);

            $formBuilder = $this->createMock(FormBuilderInterface::class);
            $form = $this->createMock(FormInterface::class);

            $this->formFactory->method('createBuilder')->willReturn($formBuilder);
            $formBuilder->method('getForm')->willReturn($form);
            $form->method('isSubmitted')->willReturn(true);

            $userBulkActionService->expects(self::once())
                ->method('apply')
                ->with($action, ['user-1'])
                ->willReturn(1);

            $this->router->method('generate')->willReturn('/incp/admin/list');

            $response = $this->controller->list(
                $request,
                $categoryRepository,
                $roleRepository,
                $userBulkActionService,
                $userRepository,
                $viewStateManager,
            );

            self::assertInstanceOf(RedirectResponse::class, $response);
        }
    }

    #[Test]
    public function listHandlesBulkActionWithoutValidActionName(): void
    {
        $request = new Request([], ['items' => ['user-1']]);
        $request->setSession($this->session);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $roleRepository = $this->createMock(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $viewStateManager = $this->createMock(ViewStateManager::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $userBulkActionService->expects(self::never())->method('apply');

        $this->router->method('generate')->willReturn('/incp/admin/list');

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function listHandlesBulkActionException(): void
    {
        $request = new Request([], ['items' => ['user-1'], 'delete' => '1']);
        $request->setSession($this->session);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $roleRepository = $this->createMock(RoleRepository::class);
        $userBulkActionService = $this->createMock(UserBulkActionService::class);
        $userRepository = $this->createMock(UserRepository::class);
        $viewStateManager = $this->createMock(ViewStateManager::class);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);

        $this->formFactory->method('createBuilder')->willReturn($formBuilder);
        $formBuilder->method('getForm')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        $userBulkActionService->expects(self::once())
            ->method('apply')
            ->willThrowException(new CannotRemoveLastAdministratorException('Cannot remove last admin'));

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('error', 'Cannot remove last admin');

        $this->router->method('generate')->willReturn('/incp/admin/list');

        $response = $this->controller->list(
            $request,
            $categoryRepository,
            $roleRepository,
            $userBulkActionService,
            $userRepository,
            $viewStateManager,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    // =========================================================================
    // edit() tests
    // =========================================================================

    #[Test]
    public function editRendersPageForExistingUserOnGet(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'john_doe');

        $user = new User();
        $user->setUsername('john_doe');
        $preference = new UserPreference($user);
        $user->setPreferences($preference);

        $imageTransformer = $this->createMock(ImageTransformer::class);
        $recoveryCodeManager = $this->createMock(RecoveryCodeManager::class);
        $totpManager = $this->createMock(TotpManager::class);
        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);
        $userProtectionService = $this->createMock(UserProtectionService::class);
        $userRepository = $this->createMock(UserRepository::class);

        $userRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['username' => 'john_doe'])
            ->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $formView = new FormView();

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(UserType::class, $user, ['validation_groups' => ['']])
            ->willReturn($form);

        $form->expects(self::once())->method('handleRequest')->with($request);
        $form->expects(self::once())->method('isSubmitted')->willReturn(false);
        $form->expects(self::once())->method('createView')->willReturn($formView);

        $imageTransformer->expects(self::once())->method('isHEICSupported')->willReturn(true);
        $recoveryCodeManager->expects(self::once())->method('getRemainingCount')->willReturn(5);
        $trustedDeviceManager->expects(self::once())->method('getCurrentTrustedDevice')->willReturn(null);
        $trustedDeviceManager->expects(self::once())->method('getTrustedDevices')->willReturn([]);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/admin/profile.html.twig', self::callback(static function (array $context) use ($user, $formView): bool {
                return $context['user'] === $user
                    && $context['form'] === $formView
                    && $context['heicSupported'] === true
                    && $context['remainingRecoveryCodes'] === 5;
            }))
            ->willReturn('<html>Profile</html>');

        $response = $this->controller->edit(
            $request,
            $imageTransformer,
            $recoveryCodeManager,
            $totpManager,
            $trustedDeviceManager,
            $userAccountEmailService,
            $userProtectionService,
            $userRepository,
        );

        self::assertSame('<html>Profile</html>', $response->getContent());
    }

    #[Test]
    public function editCreatesPreferencesIfUserHasNone(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'john_doe');

        $user = new User();
        $user->setUsername('john_doe');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->method('create')->willReturn($form);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(UserPreference::class));

        $this->twig->method('render')->willReturn('<html>Profile</html>');

        $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertNotNull($user->getPreferences());
    }

    #[Test]
    public function editCreatesNewUserIfNotFoundInRepository(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'unknown_user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->method('create')->willReturn($form);

        $this->twig->method('render')->willReturn('<html>Profile</html>');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertSame('<html>Profile</html>', $response->getContent());
    }

    #[Test]
    public function editHandlesRoleRemovalAdminProtectionException(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'admin_user');

        $adminRole = $this->createMock(Role::class);
        $adminRole->method('isAdministrator')->willReturn(true);

        $user = new User();
        $user->setUsername('admin_user');
        $user->setAssignedRoles(new ArrayCollection([$adminRole]));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createMock(UserProtectionService::class);
        $userProtectionService->expects(self::once())
            ->method('assertAdministratorCanBeRemoved')
            ->willThrowException(new CannotRemoveLastAdministratorException('Cannot remove last admin role'));

        $form = $this->createMock(FormInterface::class);
        $rolesForm = $this->createMock(FormInterface::class);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);

        // Simulate role being removed during form handling
        $user->setAssignedRoles(new ArrayCollection());

        $form->expects(self::once())->method('get')->with('assignedRoles')->willReturn($rolesForm);
        $rolesForm->expects(self::once())
            ->method('addError')
            ->with(self::callback(static fn (FormError $error): bool => $error->getMessage() === 'Cannot remove last admin role'));

        $form->method('isValid')->willReturn(false);
        $this->twig->method('render')->willReturn('<html>Form with errors</html>');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $userProtectionService,
            $userRepository,
        );

        self::assertSame('<html>Form with errors</html>', $response->getContent());
    }

    #[Test]
    public function editEnableDisableButtonSuccess(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'test_user');
        $request->setSession($this->session);

        $user = new User();
        $user->setUsername('test_user');
        $user->setActive(true);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $enableDisableButton = $this->createMock(ClickableInterface::class);
        $enableDisableButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'enableDisable');
        $form->method('get')->with('enableDisable')->willReturn($enableDisableButton);

        $this->router->method('generate')->willReturn('/incp/admin/test_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertFalse($user->isEnabled());
        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editEnableDisableButtonThrowsAdminProtectionException(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'admin_user');
        $request->setSession($this->session);

        $adminRole = $this->createMock(Role::class);
        $adminRole->method('isAdministrator')->willReturn(true);

        $user = new User();
        $user->setUsername('admin_user');
        $user->setActive(true);
        $user->setAssignedRoles(new ArrayCollection([$adminRole]));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createMock(UserProtectionService::class);
        $userProtectionService->expects(self::once())
            ->method('assertAdministratorCanBeRemoved')
            ->willThrowException(new CannotRemoveLastAdministratorException('Cannot disable last admin'));

        $form = $this->createMock(FormInterface::class);
        $enableDisableButton = $this->createMock(ClickableInterface::class);
        $enableDisableButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'enableDisable');
        $form->method('get')->with('enableDisable')->willReturn($enableDisableButton);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('error', 'Cannot disable last admin');

        $this->router->expects(self::once())
            ->method('generate')
            ->with('incp_admin_edit', ['id' => 'admin_user'])
            ->willReturn('/incp/admin/admin_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editDeleteButtonSuccess(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'test_user');
        $request->setSession($this->session);

        $user = new User();
        $user->setUsername('test_user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $deleteButton = $this->createMock(ClickableInterface::class);
        $deleteButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'delete');
        $form->method('get')->with('delete')->willReturn($deleteButton);

        $this->router->method('generate')->willReturn('/incp/admin/test_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertTrue($user->isRemoved());
        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editDeleteButtonThrowsAdminProtectionException(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'admin_user');
        $request->setSession($this->session);

        $adminRole = $this->createMock(Role::class);
        $adminRole->method('isAdministrator')->willReturn(true);

        $user = new User();
        $user->setUsername('admin_user');
        $user->setAssignedRoles(new ArrayCollection([$adminRole]));

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $userProtectionService = $this->createMock(UserProtectionService::class);
        $userProtectionService->expects(self::once())
            ->method('assertAdministratorCanBeRemoved')
            ->willThrowException(new CannotRemoveLastAdministratorException('Cannot delete last admin'));

        $form = $this->createMock(FormInterface::class);
        $deleteButton = $this->createMock(ClickableInterface::class);
        $deleteButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'delete');
        $form->method('get')->with('delete')->willReturn($deleteButton);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('error', 'Cannot delete last admin');

        $this->router->method('generate')->willReturn('/incp/admin/admin_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $userProtectionService,
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editDisableTotpButtonClicked(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'test_user');
        $request->setSession($this->session);

        $user = new User();
        $user->setUsername('test_user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $totpManager = $this->createMock(TotpManager::class);
        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);

        $totpManager->expects(self::once())->method('disable')->with($user);
        $trustedDeviceManager->expects(self::once())->method('removeAll')->with($user);

        $form = $this->createMock(FormInterface::class);
        $disableTotpButton = $this->createMock(ClickableInterface::class);
        $disableTotpButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'disableTotp');
        $form->method('get')->with('disableTotp')->willReturn($disableTotpButton);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('success', 'Two-Factor Authentication has been disabled');

        $this->router->method('generate')->willReturn('/incp/admin/test_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $totpManager,
            $trustedDeviceManager,
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editEnableTotpButtonClicked(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'test_user');

        $user = new User();
        $user->setUsername('test_user');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $enableTotpButton = $this->createMock(ClickableInterface::class);
        $enableTotpButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'enableTotp');
        $form->method('get')->with('enableTotp')->willReturn($enableTotpButton);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('incp_admin_totp_setup', [], RouterInterface::ABSOLUTE_PATH)
            ->willReturn('/incp/admin/totp-setup');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/admin/totp-setup', $response->getTargetUrl());
    }

    #[Test]
    public function editRegenerateCodesButtonClicked(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'test_user');
        $request->setSession($this->session);

        $currentUser = new User();
        $this->controller->currentUserMock = $currentUser;

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(new User());

        $recoveryCodeManager = $this->createMock(RecoveryCodeManager::class);
        $codes = ['code1', 'code2'];
        $recoveryCodeManager->expects(self::once())
            ->method('generate')
            ->with($currentUser)
            ->willReturn($codes);

        $this->session->expects(self::once())
            ->method('set')
            ->with('recovery_codes', $codes);

        $form = $this->createMock(FormInterface::class);
        $regenerateCodesButton = $this->createMock(ClickableInterface::class);
        $regenerateCodesButton->method('isClicked')->willReturn(true);

        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $form->method('has')->willReturnCallback(static fn (string $name): bool => $name === 'regenerateCodes');
        $form->method('get')->with('regenerateCodes')->willReturn($regenerateCodesButton);

        $this->router->expects(self::once())
            ->method('generate')
            ->with('incp_security_recovery_codes_generate', [], RouterInterface::ABSOLUTE_PATH)
            ->willReturn('/incp/security/recovery-codes');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $recoveryCodeManager,
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/security/recovery-codes', $response->getTargetUrl());
    }

    #[Test]
    public function editRegistersNewUserAndUpdatesPreferences(): void
    {
        $request = new Request([], ['user' => [
            'timezone' => 'UTC',
            'locale' => 'en_GB',
            'color' => '#123456',
        ]]);
        $request->attributes->set('id', 'new');
        $request->setSession($this->session);

        $userRepository = $this->createMock(UserRepository::class);
        $userAccountEmailService = $this->createMock(UserAccountEmailService::class);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(User::class));
        $this->entityManager->expects(self::once())->method('flush');

        $userAccountEmailService->expects(self::once())
            ->method('registerNewUser')
            ->with(
                self::isInstanceOf(User::class),
                self::callback(static fn (array $data): bool => isset($data['viewModel'])),
                self::isInstanceOf(\Closure::class)
            )
            ->willReturnCallback(function (User $user, array $data, \Closure $urlGenerator): void {
                $this->router->expects(self::once())
                    ->method('generate')
                    ->with('incp_account_new-password', ['token' => 'sample_token'])
                    ->willReturn('/account/new-password/sample_token');

                $urlGenerator('sample_token');
            });

        $this->router->expects(self::once())
            ->method('generate')
            ->with('incp_admin_edit', ['id' => null])
            ->willReturn('/incp/admin/edit');

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('success', 'User details saved.');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $userAccountEmailService,
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function editUpdatesExistingUserPreferencesFromRequestDefaults(): void
    {
        $request = new Request();
        $request->attributes->set('id', 'existing_user');
        $request->setSession($this->session);

        $user = new User();
        $user->setUsername('existing_user');
        $preferences = new UserPreference($user);
        $preferences->setTimezone('Europe/London');
        $preferences->setLocale('en');
        $preferences->setColor('#000000');
        $user->setPreferences($preferences);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn($user);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->method('create')->willReturn($form);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $this->entityManager->expects(self::once())->method('flush');

        $this->router->method('generate')->willReturn('/incp/admin/existing_user');

        $response = $this->controller->edit(
            $request,
            $this->createMock(ImageTransformer::class),
            $this->createMock(RecoveryCodeManager::class),
            $this->createMock(TotpManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->createMock(UserAccountEmailService::class),
            $this->createMock(UserProtectionService::class),
            $userRepository,
        );

        self::assertSame('Europe/London', $user->getPreferences()?->getTimezone());
        self::assertSame('en', $user->getPreferences()?->getLocale());
        self::assertSame('#000000', $user->getPreferences()?->getColor());
        self::assertInstanceOf(RedirectResponse::class, $response);
    }
}
