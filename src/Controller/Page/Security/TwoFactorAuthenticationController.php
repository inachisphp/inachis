<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Inachis\Enum\Security\LoginResultType;
use Inachis\Form\LoginRecoveryCodeType;
use Inachis\Form\LoginTotpType;
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TwoFactorLoginCompleter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles multi-factor authentication challenges after standard password verification.
 */
class TwoFactorAuthenticationController extends AbstractInachisController
{
    private const LOGIN_ROUTE = 'incp_account_login';

    /**
     * Display the TOTP challenge form.
     *
     * @param Request $request
     * @param TwoFactorLoginCompleter $completer
     * @return Response
     */
    #[Route('/incp/login/totp', name: 'incp_totp_login', methods: ['GET'])]
    public function totpForm(
        Request $request,
        TwoFactorLoginCompleter $completer,
    ): Response {
        if (!$completer->isValid($request)) {
            return $this->redirectToRoute(self::LOGIN_ROUTE);
        }

        return $this->render('inadmin/page/security/totp/login.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $this->createForm(LoginTotpType::class)->createView(),
        ]);
    }

    /**
     * Verify the submitted TOTP code.
     *
     * @param Request $request
     * @param TotpManager $totpManager
     * @param TwoFactorLoginCompleter $completer
     * @return Response
     */
    #[Route('/incp/login/totp', name: 'incp_totp_login_verify', methods: ['POST'])]
    public function totpVerify(
        Request $request,
        TotpManager $totpManager,
        TwoFactorLoginCompleter $completer,
    ): Response {
        if (!$completer->isValid($request)) {
            return $this->redirectToRoute(self::LOGIN_ROUTE);
        }

        $form = $this->createForm(LoginTotpType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = (string) $form->get('code')->getData();
            if ($totpManager->verify($this->getCurrentUser(), $code)) {
                return $completer->complete(
                    $request,
                    LoginResultType::TYPE_SUCCESS_TOTP,
                    (bool) $form->get('trustDevice')->getData()
                );
            }
            $this->addFlash('error', 'Invalid authentication code.');
            return $this->redirectToRoute('incp_totp_login');
        }

        return $this->render('inadmin/page/security/totp/login.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Display the Recovery Code form.
     *
     * @param Request $request
     * @param TwoFactorLoginCompleter $completer
     * @return Response
     */
    #[Route('/incp/login/recovery', name: 'incp_recovery_code_login', methods: ['GET'])]
    public function recoveryForm(
        Request $request,
        TwoFactorLoginCompleter $completer,
    ): Response  {
        if (!$completer->isValid($request)) {
            return $this->redirectToRoute(self::LOGIN_ROUTE);
        }

        $this->viewModel->page->title = 'Recovery Code';

        return $this->render('inadmin/page/security/totp/recovery_login.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $this->createForm(LoginRecoveryCodeType::class)->createView(),
        ]);
    }

    /**
     * Verify the submitted recovery code.
     *
     * @param Request $request
     * @param RecoveryCodeManager $recoveryCodeManager
     * @param TwoFactorLoginCompleter $completer
     * @return Response
     */
    #[Route('/incp/login/recovery', name: 'incp_recovery_code_verify', methods: ['POST'])]
    public function recoveryVerify(
        Request $request,
        RecoveryCodeManager $recoveryCodeManager,
        TwoFactorLoginCompleter $completer,
    ): Response {
        if (!$completer->isValid($request)) {
            return $this->redirectToRoute(self::LOGIN_ROUTE);
        }

        $form = $this->createForm(LoginRecoveryCodeType::class)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $code = (string) $form->get('code')->getData();
            if ($recoveryCodeManager->verify($this->getCurrentUser(), $code)) {
                return $completer->complete(
                    $request,
                    LoginResultType::TYPE_SUCCESS_RECOVERY,
                    false
                );
            }
            $this->addFlash('error', 'Invalid recovery code.');
            return $this->redirectToRoute('incp_recovery_code_login');
        }

        return $this->render('inadmin/page/security/totp/recovery_login.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
        ]);
    }
}
