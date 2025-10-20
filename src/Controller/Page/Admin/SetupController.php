<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Admin;

use App\Controller\AbstractInachisController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/setup", name: 'incc/setup', methods: [ "GET", "POST" ])]
    public function stage1(Request $request): Response
    {
        if ($this->entityManager->getRepository(User::class)->getAllCount() > 0) {
            return $this->redirectToRoute(
                'app_dashboard_default',
                [],
                Response::HTTP_PERMANENTLY_REDIRECT
            );
        }
        $form = $this->createFormBuilder()->getForm();
        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = 'Inachis Install';
        return $this->render('setup/stage-1.html.twig', $this->data);
    }
}
