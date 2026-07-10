<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Discovery\DiscoveryStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscoveryController extends AbstractInachisController
{
    /**
     * Main Crawlers and Discovery dashboard for viewing status
     *
     * @param DiscoveryStatusService $discoveryStatusService
     * @return Response
     */
    #[Route(
        '/incc/settings/discovery',
        name: 'incc_settings_discovery'
    )]
    public function index(
        DiscoveryStatusService $discoveryStatusService
    ): Response {
        $this->viewModel->page->title = 'Crawlers & Discovery';
        $this->viewModel->page->tab = 'discovery';

        return $this->render(
            '/inadmin/page/settings/discovery.html.twig',
            [
                'viewModel' => $this->viewModel,
                'status' => $discoveryStatusService->getGroupedStatus(),
            ]
        );
    }
}
