<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\User;
use Inachis\Entity\User\UserPreference;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Exception\User\CannotRemoveLastAdministratorException;
use Inachis\Form\UserType;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\Security\RoleRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Inachis\Service\Content\ViewStateManagerInterface;
use Inachis\Service\User\ProfileColorPalette;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserProtectionServiceInterface;
use Inachis\Transformer\ImageTransformer;
use Random\RandomException;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileController extends AbstractInachisController
{
    /**
     * List administrators.
     */
    #[Route(
        '/incp/admin/list/{limit}/{offset}',
        name: 'incp_admin_list',
        requirements: [
            'limit' => "\d+",
            'offset' => "\d+",
        ],
        defaults: ['limit' => 25, 'offset' => 0],
        methods: ['GET', 'POST'],
    )]
    #[RequiresPermission(
        resource: PermissionResource::USER,
        action: PermissionAction::VIEW,
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        RoleRepository $roleRepository,
        UserBulkActionService $userBulkActionService,
        UserRepository $userRepository,
        ViewStateManagerInterface $viewStateManager,
        FormFactoryInterface $formFactory,
    ): Response {
        $form = $formFactory->createBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            /** @var list<string> */
            $items = $request->request->all('items');

            $action = $this->getBulkAction($request);

            if (null !== $action) {
                try {
                    $count = $userBulkActionService->apply($action, $items);

                    $this->addFlash(
                        'success',
                        "Action '$action' applied to $count users.",
                    );
                } catch (CannotRemoveLastAdministratorException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            return $this->redirectToRoute('incp_admin_list');
        }

        $params = $viewStateManager->build(
            $request,
            'admin',
            new ViewStateDefaults(
                sort: 'displayName asc',
                view: 'table',
            ),
            $categoryRepository,
        );

        $this->viewModel->page->title = 'Users';
        $this->viewModel->page->tab = 'users';

        return $this->render('inadmin/page/admin/list.html.twig', [
            'viewModel' => $this->viewModel,
            'dataset' => $userRepository->getFiltered($params),
            'form' => $form->createView(),
            'query' => $params,
            'roles' => $roleRepository->getRoleNames(25),
        ]);
    }

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    #[Route(
        '/incp/admin/{id}',
        name: 'incp_admin_edit',
        methods: ['GET', 'POST'],
        priority: -100,
    )]
    public function edit(
        Request $request,
        FormFactoryInterface $formFactory,
        ImageTransformer $imageTransformer,
        RecoveryCodeManager $recoveryCodeManager,
        TotpManager $totpManager,
        TrustedDeviceManager $trustedDeviceManager,
        UserAccountEmailService $userAccountEmailService,
        UserProtectionServiceInterface $userProtectionService,
        UserRepository $userRepository,
    ): Response {
        $id = $request->attributes->getString('id');
        $isNew = 'new' === $id;

        $user = $this->findUser($request, $userRepository, $isNew);

        $preferences = $this->ensurePreferences($user);

        $originalRoles = $user->getAssignedRoles()->toArray();

        $form = $formFactory->create(UserType::class, $user, [
            'validation_groups' => [''],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->validateAdministratorRoleRemoval(
                $form,
                $originalRoles,
                $user,
                $userProtectionService,
            );

            if ($form->isValid()) {
                $response = $this->handleFormActions(
                    $request,
                    $form,
                    $user,
                    $isNew,
                    $preferences,
                    $totpManager,
                    $trustedDeviceManager,
                    $recoveryCodeManager,
                    $userProtectionService,
                );

                if (null !== $response) {
                    return $response;
                }

                if ($isNew) {
                    $this->createNewUser(
                        $user,
                        $preferences,
                        $userAccountEmailService,
                    );
                }

                $this->updatePreferences($request, $preferences);

                $this->entityManager->flush();

                $this->addFlash('success', 'User details saved.');

                return $this->redirect(
                    $this->generateUrl(
                        'incp_admin_edit',
                        ['id' => $user->getUsername()],
                    ),
                );
            }
        }

        $this->viewModel->page->title = 'Profile';
        $this->viewModel->page->tab = 'users';

        return $this->render('inadmin/page/admin/profile.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'heicSupported' => $imageTransformer->isHEICSupported(),
            'remainingRecoveryCodes' => $recoveryCodeManager->getRemainingCount(
                $this->getCurrentUser(),
            ),
            'currentTrustedDevice' => $trustedDeviceManager->getCurrentTrustedDevice(
                $user,
                $request,
            ),
            'trustedDevices' => $trustedDeviceManager->getTrustedDevices($user),
            'user' => $user,
        ]);
    }

    /**
     * Determine which bulk action was requested.
     */
    private function getBulkAction(Request $request): ?string
    {
        return match (true) {
            $request->request->has('delete') => 'delete',
            $request->request->has('enable') => 'enable',
            $request->request->has('disable') => 'disable',
            default => null,
        };
    }

    /**
     * Find an existing user or create a new one.
     */
    private function findUser(
        Request $request,
        UserRepository $userRepository,
        bool $isNew,
    ): User {
        if ($isNew) {
            return new User();
        }

        return $userRepository->findOneBy([
            'username' => $request->attributes->getString('id'),
        ]) ?? new User();
    }

    /**
     * Ensure the user has preferences.
     */
    private function ensurePreferences(User $user): UserPreference
    {
        $preferences = $user->getPreferences();

        if (null !== $preferences) {
            return $preferences;
        }

        $preferences = new UserPreference($user);
        $user->setPreferences($preferences);

        $this->entityManager->persist($preferences);

        return $preferences;
    }

    /**
     * Prevent the final administrator from being removed.
     */
    private function validateAdministratorRoleRemoval(
        FormInterface $form,
        array $originalRoles,
        User $user,
        UserProtectionServiceInterface $userProtectionService,
    ): void {
        foreach ($originalRoles as $role) {
            if (
                $role->isAdministrator()
                && !$user->getAssignedRoles()->contains($role)
            ) {
                try {
                    $userProtectionService->assertAdministratorCanBeRemoved();
                } catch (CannotRemoveLastAdministratorException $e) {
                    $form->get('assignedRoles')->addError(
                        new FormError($e->getMessage()),
                    );
                }

                break;
            }
        }
    }

    /**
     * Handle submit buttons that perform an immediate action.
     *
     * Returns a response when the request should end immediately,
     * otherwise null when normal saving should continue.
     *
     * @throws RandomException
     */
    private function handleFormActions(
        Request $request,
        FormInterface $form,
        User $user,
        bool $isNew,
        UserPreference $preferences,
        TotpManager $totpManager,
        TrustedDeviceManager $trustedDeviceManager,
        RecoveryCodeManager $recoveryCodeManager,
        UserProtectionServiceInterface $userProtectionService,
    ): ?Response {
        if ($this->isClicked($form, 'enableDisable')) {
            return $this->handleEnableDisable(
                $user,
                $userProtectionService,
            );
        }

        if ($this->isClicked($form, 'delete')) {
            return $this->handleDelete(
                $user,
                $userProtectionService,
            );
        }

        if ($this->isClicked($form, 'disableTotp')) {
            return $this->handleDisableTotp(
                $user,
                $totpManager,
                $trustedDeviceManager,
            );
        }

        if ($this->isClicked($form, 'enableTotp')) {
            return $this->redirectToRoute('incp_admin_totp_setup');
        }

        if ($this->isClicked($form, 'regenerateCodes')) {
            $codes = $recoveryCodeManager->generate(
                $this->getCurrentUser(),
            );

            $request->getSession()->set('recovery_codes', $codes);

            return $this->redirectToRoute(
                'incp_security_recovery_codes_generate',
            );
        }

        return null;
    }

    /**
     * Check whether a submit button exists and was clicked.
     */
    private function isClicked(
        FormInterface $form,
        string $name,
    ): bool {
        if (!$form->has($name)) {
            return false;
        }

        $button = $form->get($name);

        return $button instanceof ClickableInterface
            && $button->isClicked();
    }

    /**
     * Enable or disable a user.
     */
    private function handleEnableDisable(
        User $user,
        UserProtectionServiceInterface $userProtectionService,
    ): ?Response {
        try {
            if ($user->isEnabled() && $user->isAdministrator()) {
                $userProtectionService->assertAdministratorCanBeRemoved();
            }

            $user->setActive(!$user->isEnabled());
        } catch (CannotRemoveLastAdministratorException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('incp_admin_edit', [
                'id' => $user->getUsername(),
            ]);
        }

        return null;
    }

    /**
     * Mark a user as removed.
     */
    private function handleDelete(
        User $user,
        UserProtectionServiceInterface $userProtectionService,
    ): ?Response {
        try {
            if ($user->isAdministrator()) {
                $userProtectionService->assertAdministratorCanBeRemoved();
            }

            $user->setRemoved(true);
        } catch (CannotRemoveLastAdministratorException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('incp_admin_edit', [
                'id' => $user->getUsername(),
            ]);
        }

        return null;
    }

    /**
     * Disable TOTP and remove trusted devices.
     */
    private function handleDisableTotp(
        User $user,
        TotpManager $totpManager,
        TrustedDeviceManager $trustedDeviceManager,
    ): Response {
        $totpManager->disable($user);
        $trustedDeviceManager->removeAll($user);

        $this->addFlash(
            'success',
            'Two-Factor Authentication has been disabled',
        );

        return $this->redirectToRoute('incp_admin_edit', [
            'id' => $user->getUsername(),
        ]);
    }

    /**
     * Create and register a new user.
     *
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    private function createNewUser(
        User $user,
        UserPreference $preferences,
        UserAccountEmailService $userAccountEmailService,
    ): void {
        $preferences->setColor(ProfileColorPalette::generate());

        $this->entityManager->persist($user);

        $userAccountEmailService->registerNewUser(
            $user,
            ['viewModel' => $this->viewModel],
            fn (string $token) => $this->generateUrl(
                'incp_account_new-password',
                ['token' => $token],
            ),
        );
    }

    /**
     * Update user preferences from the request.
     */
    private function updatePreferences(
        Request $request,
        UserPreference $preferences,
    ): void {
        $userData = $request->request->all('user');

        $preferences->setTimezone(
            $userData['timezone'] ?? $preferences->getTimezone(),
        );

        $preferences->setLocale(
            $userData['locale'] ?? $preferences->getLocale(),
        );

        $preferences->setColor(
            $userData['color'] ?? $preferences->getColor(),
        );
    }
}
