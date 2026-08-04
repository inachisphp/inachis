<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\VersionService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsIndexController extends AbstractInachisController
{
    /**
     * List of setting pages.
     */
    #[Route('/incp/settings', name: 'incp_settings_index')]
    public function index(VersionService $versionService): Response
    {
        $this->viewModel->page->title = 'Settings';
        $this->viewModel->page->tab = 'settings';

        return $this->render('inadmin/page/settings/list.html.twig', [
            'viewModel' => $this->viewModel,
            'version' => $versionService->getAll(),
        ]);
    }
}
