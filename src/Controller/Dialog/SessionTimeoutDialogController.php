<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Session timeout dialog controller.
 */
class SessionTimeoutDialogController extends AbstractInachisController
{
    /**
     * Keep alive.
     */
    #[Route('/incp/keep-alive', methods: ['POST'])]
    public function keepAlive(): JsonResponse
    {
        return new JsonResponse([
            'time' => date(
                'Y-m-d\TH:i:s\Z',
                time() + (int) ini_get('session.gc_maxlifetime'),
            ),
        ]);
    }

    /**
     * Show dialog.
     */
    #[Route('/incp/ax/sessionTimeout/get', methods: ['POST'])]
    public function showDialog(Request $request): Response
    {
        return $this->render('inadmin/dialog/session_timeout.html.twig');
    }
}
