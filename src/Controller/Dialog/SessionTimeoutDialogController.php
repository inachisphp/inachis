<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Session timeout dialog controller
 */
#[IsGranted('ROLE_ADMIN')]
class SessionTimeoutDialogController extends AbstractInachisController
{
    /**
     * Keep alive
     * 
     * @return JsonResponse
     */
    #[Route('/incc/keep-alive', methods: [ 'POST' ])]
    public function keepAlive(): JsonResponse
    {
        return new JsonResponse([
            'time' => date(
                'Y-m-d\TH:i:s\Z',
                time() + (int) ini_get('session.gc_maxlifetime')
            )
        ]);
    }

    /**
     * Show dialog
     * 
     * @param Request $request
     * @return Response
     */
    #[Route('/incc/ax/sessionTimeout/get', methods: [ 'POST' ])]
    public function showDialog(Request $request): Response
    {
        return $this->render('inadmin/dialog/session_timeout.html.twig', $this->data);
    }
}
