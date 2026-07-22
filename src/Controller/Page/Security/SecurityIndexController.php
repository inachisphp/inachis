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
use Symfony\Component\Routing\Attribute\Route;

class SecurityIndexController extends AbstractInachisController
{
    /**
     * List of setting pages
     *
     * @return Response
     */
    #[Route("/incc/security", name: 'incc_security_list')]
    public function index(): Response
    {
        $this->viewModel->page->title = 'Security & Diagnostics';
        $this->viewModel->page->tab = 'security';
        return $this->render('inadmin/page/security/list.html.twig', [
            'viewModel' => $this->viewModel,
        ]);
    }
}
