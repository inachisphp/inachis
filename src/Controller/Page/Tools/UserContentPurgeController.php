<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Form\System\ContentPurgeType;
use Inachis\Service\System\DatabasePurgeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserContentPurgeController extends AbstractInachisController
{
    #[Route(
        '/incp/tools/purge',
        name: 'incp_user_content_purge',
        methods: ['GET', 'POST']
    )]
    public function purge(
        Request $request,
        DatabasePurgeService $purgeService,
    ): Response {
        $form = $this->createForm(ContentPurgeType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && 
            $form->has('acknowledge') && $form->get('acknowledge')
        ) {
            $purgeService->purgeUserTables();

            $this->addFlash(
                'success',
                'User-generated content has been permanently purged.'
            );

            return $this->redirectToRoute('incp_user_content_purge');
        }

        $this->viewModel->page->title = 'Purge Data';
        $this->viewModel->page->tab = 'tools';

        return $this->render('inadmin/page/tools/purge.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form,
        ]);
    }
}
