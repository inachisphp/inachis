<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Security;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityIndexController extends AbstractInachisController
{
    /**
     * List of setting pages.
     */
    #[Route('/incp/security', name: 'incp_security_list')]
    public function index(): Response
    {
        $this->viewModel->page->title = 'Security & Privacy';
        $this->viewModel->page->tab = 'security';

        return $this->render('inadmin/page/security/list.html.twig', [
            'viewModel' => $this->viewModel,
        ]);
    }
}
