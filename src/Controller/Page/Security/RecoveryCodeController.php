<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Manages TOTP recovery codes.
 */
class RecoveryCodeController extends AbstractInachisController
{
    /**
     * Display newly generated recovery codes.
     *
     * Recovery codes are only available immediately after generation.
     * If no codes are present in the flash bag, the user is redirected
     * back to the security page.
     *
     * The flash bag automatically removes the codes after they have
     * been retrieved once.
     *
     * @param SessionInterface $session
     * @return Response
     */
    #[Route(
        '/incc/security/recovery-codes',
        name: 'incc_security_recovery_codes_generate',
        methods: ['GET']
    )]
    public function show(SessionInterface $session): Response
    {
        $codes = $session->get('recovery_codes');
        $session->set('recovery_codes', '');

        if ($codes === []) {
            $this->addFlash(
                'warning',
                'Recovery codes are only shown immediately after they are generated.'
            );

            return $this->redirectToRoute(
                'incc_admin_list', [
                    'id' => $this->getCurrentUser()->getUsername(),
                ]
            );
        }

        $this->viewModel->page->title = 'Your Recovery Codes';

        return $this->render(
            'inadmin/page/security/totp/recovery_codes_show.html.twig',
            [
                'viewModel' => $this->viewModel,
                'codes' => $codes,
            ]
        );
    }
}
