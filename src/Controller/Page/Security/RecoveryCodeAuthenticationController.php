<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\User;
use Inachis\Form\LoginRecoveryCodeType;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RecoveryCodeAuthenticationController extends AbstractInachisController
{
    /**
     * Display the recovery code form.
     */
    #[Route(
        '/incc/login/recovery',
        name: 'inadmin_recovery_code_login',
        methods: ['GET']
    )]
    public function index(
        SessionInterface $session
    ): Response {
        if (!$session->has('security.totp_pending')) {
            return $this->redirectToRoute(
                'incc_account_login'
            );
        }

        $this->viewModel->page->title = 'Recovery Code';

        return $this->render(
            'inadmin/page/security/totp/recovery_login.html.twig',
            [
                'viewModel' => $this->viewModel,
                'form' => $this->createForm(
                    LoginRecoveryCodeType::class
                )->createView(),
            ]
        );
    }

    /**
     * Verify the submitted recovery code.
     */
    #[Route(
        '/incc/login/recovery',
        name: 'inadmin_recovery_code_verify',
        methods: ['POST']
    )]
    public function verify(
        Request $request,
        SessionInterface $session,
        RecoveryCodeManager $recoveryCodeManager,
        TokenStorageInterface $tokenStorage,
    ): Response {
        if (!$session->has('security.totp_pending')) {
            return $this->redirectToRoute(
                'incc_account_login'
            );
        }

        /** @var User|null $user */
        $user = $this->getCurrentUser();

        if ($user === null) {
            $session->remove('security.totp_pending');

            return $this->redirectToRoute(
                'incc_account_login'
            );
        }

        $form = $this->createForm(
            LoginRecoveryCodeType::class
        );

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render(
                'security/recovery_codes/login.html.twig',
                [
                    'viewModel' => $this->viewModel,
                    'form' => $form->createView(),
                ]
            );
        }

        $code = (string) $form->get('code')->getData();

        if (
            !$recoveryCodeManager->verify(
                $user,
                $code
            )
        ) {
            $this->addFlash(
                'error',
                'Invalid recovery code.'
            );

            return $this->redirectToRoute(
                'inadmin_recovery_code_login'
            );
        }

        /*
         * Recovery code accepted.
         */

        $session->remove('security.totp_pending');

        $tokenStorage->setToken(
            new UsernamePasswordToken(
                $user,
                'incc',
                $user->getRoles()
            )
        );

        $request->getSession()->save();

        return $this->redirectToRoute(
            'incc_dashboard'
        );
    }
}