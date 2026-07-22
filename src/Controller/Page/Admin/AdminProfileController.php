<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\{User,UserPreference};
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
use Inachis\Service\Content\ViewStateManager;
use Inachis\Service\User\UserBulkActionService;
use Inachis\Service\User\UserAccountEmailService;
use Inachis\Transformer\ImageTransformer;
use Inachis\Service\User\ProfileColorPalette;
use Inachis\Service\User\UserProtectionService;
use Random\RandomException;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminProfileController extends AbstractInachisController
{
    /**
     * List administrators
     *
     * @param Request $request
     * @param UserBulkActionService $userBulkActionService
     * @param UserRepository $userRepository
     * @param ViewStateManager $viewStateManager
     * @return Response
     */
    #[Route(
        "/incc/admin/list/{limit}/{offset}",
        name: 'incc_admin_list',
        requirements: [
            "limit" => "\d+",
            "offset" => "\d+",
        ],
        defaults: [ "limit" => 25, "offset" => 0, ],
        methods: [ "GET", "POST" ]
    )]
    #[RequiresPermission(
        resource: PermissionResource::USER,
        action: PermissionAction::VIEW
    )]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository,
        RoleRepository $roleRepository,
        UserBulkActionService $userBulkActionService,
        UserRepository $userRepository,
        ViewStateManager $viewStateManager,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && !empty($request->request->all('items'))) {
            /** @var list<string> */
            $items = $request->request->all('items');
            $action = $request->request->has('delete')  ? 'delete' :
                ($request->request->has('enable') ? 'enable' :
                ($request->request->has('disable') ? 'disable' : null));

            if ($action !== null) {
                try {
                    $count = $userBulkActionService->apply($action, $items);
                    $this->addFlash('success', "Action '$action' applied to $count users.");
                } catch (CannotRemoveLastAdministratorException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            return $this->redirectToRoute('incc_admin_list');
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
     * @param Request $request
     * @param ImageTransformer $imageTransformer
     * @param UserAccountEmailService $userAccountEmailService
     * @param UserRepository $userRepository
     * @return Response
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    #[Route(
        "/incc/admin/{id}",
        name: "incc_admin_edit",
        methods: [ "GET", "POST" ],
        priority: -100
    )]
    public function edit(
        Request $request,
        ImageTransformer $imageTransformer,
        RecoveryCodeManager $recoveryCodeManager,
        TotpManager $totpManager,
        TrustedDeviceManager $trustedDeviceManager,
        UserAccountEmailService $userAccountEmailService,
        UserProtectionService $userProtectionService,
        UserRepository $userRepository,
    ): Response {
        $id = $request->attributes->getString('id');
        $isNew = ($id === 'new');

        $user = $isNew ? new User():
            $userRepository->findOneBy(
                [ 'username' => $request->attributes->getString('id') ]
            ) ?? new User();
        $preferences = $user->getPreferences();
        if ($preferences === null) {
            $preferences = new UserPreference($user);
            $user->setPreferences($preferences);
            $this->entityManager->persist($preferences);
        }
        $originalRoles = $user->getAssignedRoles()->toArray();

        /** @var Form $form */
        $form = $this->createForm(UserType::class, $user, [
            'validation_groups' => [ '' ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($originalRoles as $role) {
                if (
                    $role->isAdministrator() &&
                    !$user->getAssignedRoles()->contains($role)
                ) {
                    try {
                        $userProtectionService->assertAdministratorCanBeRemoved();
                    } catch (CannotRemoveLastAdministratorException $e) {
                        $form->get('assignedRoles')->addError(
                            new \Symfony\Component\Form\FormError($e->getMessage())
                        );
                    }

                    break;
                }
            }

            if ($form->isValid()) {
                $enableDisable = $form->has('enableDisable') ? $form->get('enableDisable') : null;
                $delete = $form->has('delete') ? $form->get('delete') : null;
                $disableTotp = $form->has('disableTotp') ? $form->get('disableTotp') : null;
                $enableTotp = $form->has('enableTotp') ? $form->get('enableTotp') : null;
                $regenerateCodes = $form->has('regenerateCodes') ? $form->get('regenerateCodes') : null;

                if ($enableDisable instanceof \Symfony\Component\Form\ClickableInterface && $enableDisable->isClicked()) {
                    try {
                        if ($user->isEnabled() && $user->isAdministrator()) {
                            $userProtectionService->assertAdministratorCanBeRemoved();
                        }

                        $user->setActive(!$user->isEnabled());
                    } catch (CannotRemoveLastAdministratorException $e) {
                        $this->addFlash('error', $e->getMessage());

                        return $this->redirectToRoute('incc_admin_edit', [
                            'id' => $user->getUsername(),
                        ]);
                    }
                }
                if ($delete instanceof \Symfony\Component\Form\ClickableInterface && $delete->isClicked()) {
                    try {
                        if ($user->isAdministrator()) {
                            $userProtectionService->assertAdministratorCanBeRemoved();
                        }

                        $user->setRemoved(true);
                    } catch (CannotRemoveLastAdministratorException $e) {
                        $this->addFlash('error', $e->getMessage());

                        return $this->redirectToRoute('incc_admin_edit', [
                            'id' => $user->getUsername(),
                        ]);
                    }
                }
                if ($disableTotp instanceof \Symfony\Component\Form\ClickableInterface && $disableTotp->isClicked()) {
                    // todo: change this to disable not remove?
                    $totpManager->disable($user);
                    $trustedDeviceManager->removeAll($user);
                    $this->addFlash('success', 'Two-Factor Authentication has been disabled');

                    return $this->redirectToRoute('incc_admin_edit', [
                        'id' => $user->getUsername(),
                    ]);
                }
                if ($enableTotp instanceof \Symfony\Component\Form\ClickableInterface && $enableTotp->isClicked()) {
                    return $this->redirectToRoute('incc_admin_totp_setup');
                }
                if ($regenerateCodes instanceof \Symfony\Component\Form\ClickableInterface && $regenerateCodes->isClicked()) {
                    $codes = $recoveryCodeManager->generate($this->getCurrentUser());
                    $request->getSession()->set('recovery_codes', $codes);
                    return $this->redirectToRoute('incc_security_recovery_codes_generate');
                }

                if ($isNew) {
                    $preferences->setColor(ProfileColorPalette::generate());
                    $this->entityManager->persist($user);
                    $userAccountEmailService->registerNewUser(
                        $user,
                        [ 'viewModel' => $this->viewModel, ],
                        fn (string $token) => $this->generateUrl(
                            'incc_account_new-password',
                            [ 'token' => $token ]
                        )
                    );
                }
                $preferences->setTimezone(
                    $request->request->all('user')['timezone'] ?? $preferences->getTimezone()
                );
                $preferences->setLocale(
                    $request->request->all('user')['locale'] ?? $preferences->getLocale()
                );
                $preferences->setColor(
                    $request->request->all('user')['color'] ?? $preferences->getColor()
                );

                $this->entityManager->flush();

                $this->addFlash('success', 'User details saved.');
                return $this->redirect($this->generateUrl('incc_admin_edit', [
                    'id' => $user->getUsername(),
                ]));
            }
        }

        $this->viewModel->page->title = 'Profile';
        $this->viewModel->page->tab = 'users';
        return $this->render('inadmin/page/admin/profile.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'heicSupported' => $imageTransformer->isHEICSupported(),
            'remainingRecoveryCodes' => $recoveryCodeManager->getRemainingCount(
                $this->getCurrentUser()
            ),
            'currentTrustedDevice' => $trustedDeviceManager->getCurrentTrustedDevice(
                $user,
                $request
            ),
            'trustedDevices' => $trustedDeviceManager->getTrustedDevices($user),
            'user' => $user,
        ]);
    }
}
