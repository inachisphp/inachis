<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Dialog;

use App\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ConfirmationController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/confirmation/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        $this->data['title'] = $request->request->get('title', '') ?: sprintf(
            '<%s>',
            $this->translator->trans('admin.dialog.confirm.default.title', [], 'messages'),
        );
        $this->data['entity'] = $request->request->get('entity', '');
        $this->data['warning'] = $request->request->get('warning', '') ?:
            $this->translator->trans('admin.dialog.confirm.default.warning', [], 'messages');
        return $this->render('inadmin/dialog/confirmation.html.twig', $this->data);
    }
}
