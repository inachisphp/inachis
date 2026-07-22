<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Enum\Security\PermissionAction;
use Inachis\Enum\Security\PermissionResource;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Repository\Content\CategoryRepository;
use Inachis\Repository\User\LoginActivityRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Attribute\RequiresPermission;
use Inachis\Service\Content\ViewStateManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Login activity controller
 */
class LoginActivityController extends AbstractInachisController
{
    /**
     * Login activity index
     *
     * @param LoginActivityRepository $repository
     * @param Request $request
     * @return Response
     */
    #[Route('/incp/admin/login-activity', name: 'incp_admin_login_activity_index')]
    #[RequiresPermission(
        resource: PermissionResource::AUDIT_LOG,
        action: PermissionAction::VIEW
    )]
    public function index(
        CategoryRepository $categoryRepository,
        LoginActivityRepository $repository,
        Request $request,
        ViewStateManager $viewStateManager,
    ): Response {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $params = $viewStateManager->build(
            $request,
            'admin',
            new ViewStateDefaults(
                sort: 'loggedAt desc',
                view: 'table',
            ),
            $categoryRepository,
        );

        $this->viewModel->page->title = 'Login Activity';
        $this->viewModel->page->tab = 'audit-logs';
        return $this->render('inadmin/page/admin/login-activity.html.twig', [
            'viewModel' => $this->viewModel,
            'activities' => $repository->getFiltered($params),
            'form' => $form->createView(),
            'errors' => [
                // 'failedLogins' => $repository->recentFailures(),
                // 'newDevices' => $repository->newDeviceLogins(),
            ],
            'query' => $params,
        ]);
    }

    /**
     * Login activity by user
     *
     * @param Request $request
     * @param UserRepository $userRepository
     * @param LoginActivityRepository $repository
     * @return Response
     */
    #[Route('/incp/admin/{id}/login-activity', name: 'incp_admin_login_activity')]
    #[RequiresPermission(
        resource: PermissionResource::USER,
        action: PermissionAction::VIEW
    )]
    public function loginActivity(Request $request, UserRepository $userRepository, LoginActivityRepository $repository): Response
    {
        $user = $userRepository->findByUsername($request->attributes->getString('id'));
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $this->viewModel->page->title = 'Login Activity';
        $this->viewModel->page->tab = 'audit-logs';
        return $this->render('inadmin/page/admin/login-activity.html.twig', [
            'viewModel' => $this->viewModel,
            'activities' => $repository->findByUser($user, 100),
            'user' => $user,
        ]);
    }

    /**
     * Login activity view
     *
     * @param Request $request
     * @param LoginActivityRepository $repository
     * @return Response
     */
    #[Route('/incp/admin/login-activity/{id}', name: 'incp_admin_all_login_activity_view')]
    #[Route('/incp/admin/{username}/login-activity/{id}', name: 'incp_admin_login_activity_view')]
    #[RequiresPermission(
        resource: PermissionResource::AUDIT_LOG,
        action: PermissionAction::VIEW
    )]
    public function view(Request $request, LoginActivityRepository $repository): Response
    {
        $this->viewModel->page->title = 'Login Activity';
        $this->viewModel->page->tab = 'audit-logs';
        return $this->render('inadmin/page/admin/login-activity-view.html.twig', [
            'viewModel' => $this->viewModel,
            'activity' => $repository->find($request->attributes->getString('id')),
            'username' => $request->attributes->getString('username', ''),
        ]);
    }
}
