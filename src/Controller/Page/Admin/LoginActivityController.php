<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\LoginActivityRepository;
use Inachis\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for login activity
 */
#[IsGranted('ROLE_ADMIN')]
class LoginActivityController extends AbstractInachisController
{
    /**
     * @param LoginActivityRepository $repository
     * @return Response
     */
    #[Route('/incc/admin/login-activity', name: 'incc_admin_login_activity_index')]
    public function index(LoginActivityRepository $repository, Request $request): Response
    {
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        $this->data['page']['title'] = 'Login Activity';
        $this->data['page']['tab'] = 'audit-logs';
        $this->data['form'] = $form->createView();
        $this->data['activities'] = $repository->findRecent(100);
        $this->data['errors'] = [
            // 'failedLogins' => $repository->recentFailures(),
            // 'newDevices' => $repository->newDeviceLogins(),
        ];
        return $this->render('inadmin/page/admin/login-activity.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param LoginActivityRepository $repository
     * @return Response
     */
    #[Route('/incc/admin/{id}/login-activity', name: 'incc_admin_login_activity')]
    public function loginActivity(Request $request, UserRepository $userRepository, LoginActivityRepository $repository): Response
    {
        $user = $userRepository->findByUsername($request->attributes->get('id'));
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $this->data['page']['title'] = 'Login Activity';
        $this->data['page']['tab'] = 'audit-logs';
        $this->data['user'] = $user;
        $this->data['activities'] = $repository->findByUser($user, 100);
        return $this->render('inadmin/page/admin/login-activity.html.twig', $this->data);
    }

    /**
     * @param LoginActivity $activity
     * @return Response
     */
    #[Route('/incc/admin/login-activity/{id}', name: 'incc_admin_all_login_activity_view')]
    #[Route('/incc/admin/{username}/login-activity/{id}', name: 'incc_admin_login_activity_view')]
    public function view(Request $request, LoginActivityRepository $repository): Response
    {
        $this->data['page']['title'] = 'Login Activity';
        $this->data['page']['tab'] = 'audit-logs';
        $this->data['username'] = $request->attributes->get('username') ?? null;
        $this->data['activity'] = $repository->find($request->attributes->get('id'));
        return $this->render('inadmin/page/admin/login-activity-view.html.twig', $this->data);
    }
}
