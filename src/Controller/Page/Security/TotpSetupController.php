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
use Inachis\Security\Authentication\RecoveryCodeManager;
use Inachis\Security\Authentication\TotpManager;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles TOTP enrolment.
 */
class TotpSetupController extends AbstractInachisController
{
    /**
     * Display the TOTP setup page.
     *
     * Generates a temporary secret which is only persisted
     * after successful verification.
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route('/incc/security/totp/setup', name: 'incc_admin_totp_setup', methods: ['GET'])]
    public function setup(
        SessionInterface $session,
        TotpManager $totpManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        /*
         * Avoid generating a new secret if the user refreshes
         * the page during setup.
         */
        $existingSecret = $session->get(
            'totp.setup.secret'
        );

        if ($existingSecret === null) {
            $setup = $totpManager->beginSetup($user);
            $session->set('totp.setup.secret', $setup['secret']);
            $session->set('totp.setup.uri', $setup['uri']);
            $session->set('totp.setup.qr', $setup['qrCode']);
        }

        $this->viewModel->page->title = 'Enable Two-Factor Authentication';
        $this->viewModel->page->tab = 'users';

        return $this->render(
            'inadmin/page/security/totp/setup.html.twig',
            [
                'viewModel' => $this->viewModel,

                'qrCode' => $session->get('totp.setup.qr'),
                'secret' => $session->get('totp.setup.secret'),
            ]
        );
    }

    /**
     * Confirm TOTP setup.
     *
     * @param Request $request
     * @param SessionInterface $session
     * @param TrustedDeviceManager $trustedDeviceManager
     * @return Response
     */
    #[Route('/incc/security/totp/setup', name: 'incc_admin_totp_confirm', methods: ['POST'])]
    public function confirm(
        RecoveryCodeManager $recoveryCodeManager,
        Request $request,
        SessionInterface $session,
        TotpManager $totpManager,
        TrustedDeviceManager $trustedDeviceManager,
    ): Response {
        $user = $this->getCurrentUser();
        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        $secret = $session->get('totp.setup.secret');
        if ($secret === null) {
            return $this->redirectToRoute(
                'incc_admin_totp_confirm'
            );
        }

        $code = (string) $request->request->get('code');

        if (!$totpManager->confirmSetup(
            $user,
            $secret,
            $code
        )) {
            $this->addFlash('error', 'The authentication code was invalid.');

            return $this->redirectToRoute(
                'incc_admin_totp_confirm'
            );
        }

        $trustedDeviceManager->removeAll($user);
        
        /*
         * Remove the temporary secret once it has
         * successfully been persisted.
         */
        $session->remove('totp.setup.secret');
        $session->remove('totp.setup.uri');
        $session->remove('totp.setup.qr');

        $this->addFlash(
            'success',
            'Two-factor authentication has been enabled.'
        );
        $codes = $recoveryCodeManager->generate($this->getCurrentUser());
        $request->getSession()->set('recovery_codes', $codes);

        return $this->redirectToRoute('incc_security_recovery_codes_generate');
    }

    /**
     * Cancel TOTP setup.
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route('/incc/security/totp/setup/cancel', name: 'incc_admin_totp_cancel', methods: ['GET'])]
    public function cancel(
        SessionInterface $session,
    ): Response {
        $session->remove('totp.setup.secret');
        $session->remove('totp.setup.uri');
        $session->remove('totp.setup.qr');

        return $this->redirectToRoute('incc_admin_edit', [
            'id' => $this->getCurrentUser()->getUsername(),
        ]);
    }
}