<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
    #[Route("/incp/ax/confirmation/get", methods: [ "POST" ])]
    public function contentList(Request $request): Response
    {
        $data = [
            'title' => $request->request->getString('title', '') ?: sprintf(
                '<%s>',
                $this->translator->trans('admin.dialog.confirm.default.title', [], 'messages'),
            ),
            'entity' => $request->request->getString('entity', ''),
            'warning' => $request->request->getString('warning', '') ?:
                $this->translator->trans('admin.dialog.confirm.default.warning', [], 'messages'),
        ];
        if ($request->request->getBoolean('hideHelp', false)) {
            $data['hideHelp'] = $request->request->getBoolean('hideHelp', false);
        }
        return $this->render('inadmin/dialog/confirmation.html.twig', $data);
    }
}
