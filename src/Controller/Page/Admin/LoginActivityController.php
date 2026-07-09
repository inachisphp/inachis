<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\User\LoginActivityRepository;
use Inachis\Repository\User\UserRepository;
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
    #[Route('/incc/admin/login-activity', name: 'incc_admin_login_activity_index')]
    public function index(LoginActivityRepository $repository, Request $request): Response
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $this->viewModel->page->title = 'Login Activity';
        $this->viewModel->page->tab = 'audit-logs';
        return $this->render('inadmin/page/admin/login-activity.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'activities' => $repository->findRecent(100),
            'errors' => [
                // 'failedLogins' => $repository->recentFailures(),
                // 'newDevices' => $repository->newDeviceLogins(),
            ],
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
    #[Route('/incc/admin/{id}/login-activity', name: 'incc_admin_login_activity')]
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
    #[Route('/incc/admin/login-activity/{id}', name: 'incc_admin_all_login_activity_view')]
    #[Route('/incc/admin/{username}/login-activity/{id}', name: 'incc_admin_login_activity_view')]
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
