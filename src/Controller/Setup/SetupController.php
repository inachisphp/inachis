<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Setup;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User;
use Inachis\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/setup", name: 'incc_setup_stage1', methods: [ "GET", "POST" ])]
    public function stage1(UserRepository $userRepository): Response
    {
        if ($userRepository->getAllCount() > 0) {
            return $this->redirectToRoute(
                'incc_dashboard',
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
