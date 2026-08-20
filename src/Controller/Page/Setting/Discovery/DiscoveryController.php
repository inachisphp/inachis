<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting\Discovery;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Discovery\DiscoveryStatusService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscoveryController extends AbstractInachisController
{
    /**
     * Main Crawlers and Discovery dashboard for viewing status.
     */
    #[Route(
        '/incp/settings/discovery',
        name: 'incp_settings_discovery',
    )]
    public function index(
        DiscoveryStatusService $discoveryStatusService,
    ): Response {
        $this->viewModel->page->title = 'Crawlers & Discovery';
        $this->viewModel->page->tab = 'discovery';

        return $this->render(
            '/inadmin/page/settings/discovery.html.twig',
            [
                'viewModel' => $this->viewModel,
                'status' => $discoveryStatusService->getGroupedStatus(),
            ],
        );
    }
}
