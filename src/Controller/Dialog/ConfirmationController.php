<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Dialog;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConfirmationController extends AbstractInachisController
{
    /**
     * Renders a confirmation dialog
     *
     * @param Request $request
     * @return Response
     */
    #[Route("/incc/ax/confirmation/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {

        return $this->render('inadmin/dialog/confirmation.html.twig', [
            'title' => $request->request->getString('title', '') ?: sprintf(
                '<%s>',
                $this->translator->trans('admin.dialog.confirm.default.title', [], 'messages'),
            ),
            'entity' => $request->request->getString('entity', ''),
            'warning' => $request->request->getString('warning', '') ?:
                $this->translator->trans('admin.dialog.confirm.default.warning', [], 'messages'),
        ]);
    }
}
