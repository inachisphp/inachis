<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Entity\User\User;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Controller\AbstractInachisController;
use Inachis\Form\LoginTotpType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles the TOTP challenge after password authentication.
 */
class TotpAuthenticationController extends AbstractInachisController
{
    /**
     * Display the TOTP challenge form.
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route('/incc/login/totp', name: 'inadmin_totp_login', methods: ['GET'])]
    public function index(
        SessionInterface $session,
    ): Response {
        $user = $this->getCurrentUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('incc_account_login');
        }

        $form = $this->createForm(LoginTotpType::class);

        return $this->render('inadmin/page/security/totp/login.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
        ]);
    }


    /**
     * Verify the submitted TOTP code.
     *
     * @param Request $request
     * @param SessionInterface $session
     * @param TotpManager $totpManager
     * @return Response
     */
    #[Route('/incc/login/totp', name: 'inadmin_totp_login_verify', methods: ['POST'])]
    public function verify(
        Request $request,
        SessionInterface $session,
        TotpManager $totpManager,
    ): Response {
        $user = $this->getCurrentUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('incc_account_login');
        }
        
        $form = $this->createForm(LoginTotpType::class);
        $form->handleRequest($request);

        $code = (string) $form->get('code')->getData();
        if (!$totpManager->verify($user, $code)) {
            $this->addFlash(
                'error',
                'Invalid authentication code.'
            );
            return $this->redirectToRoute('inadmin_totp_login');
        }

        $session->remove('security.totp_pending');
        return $this->redirectToRoute('incc_dashboard');
    }
}
