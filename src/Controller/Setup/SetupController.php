<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Setup;

use Inachis\Controller\AbstractInachisController;
use Inachis\Repository\User\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SetupController extends AbstractInachisController
{
    #[Route('/setup', name: 'incp_setup_stage1', methods: ['GET', 'POST'])]
    public function stage1(UserRepository $userRepository): Response
    {
        if ($userRepository->getAllCount() > 0) {
            return $this->redirectToRoute(
                'incp_dashboard',
                [],
                Response::HTTP_PERMANENTLY_REDIRECT,
            );
        }
        $form = $this->createFormBuilder()->getForm();

        $this->viewModel->page->title = 'Inachis Install';

        return $this->render('setup/stage-1.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
        ]);
    }
}
