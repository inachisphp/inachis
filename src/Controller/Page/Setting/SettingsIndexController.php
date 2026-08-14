<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Discovery\DiscoveryStatusService;
use Inachis\Service\Navigation\NavigationTabService;
use Inachis\Service\System\VersionService;
use Inachis\Service\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SettingsIndexController extends AbstractInachisController
{
    /**
     * List of setting pages.
     */
    #[Route('/incp/settings', name: 'incp_settings_index')]
    public function index(
        DiscoveryStatusService $discoveryStatusService,
        NavigationTabService $tabManager,
        ThemeManager $themeManager,
        VersionService $versionService,
    ): Response {
        $discoveryStatus = $discoveryStatusService->getGroupedStatus();
        $allItems = array_merge(
            $discoveryStatus['documents'] ?? [],
            $discoveryStatus['generated'] ?? [],
        );

        // Check if any item contains error/warning messages
        $hasIssues = false;
        foreach ($allItems as $item) {
            if (!empty($item->messages)) {
                $hasIssues = true;
                break;
            }
        }

        $discoverySummary = [
            'hasIssues' => $hasIssues,
            'label' => $hasIssues ? 'Issues identified' : 'OK',
            'badgeClass' => $hasIssues ? 'badge--warning' : 'badge--success',
        ];

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/var/uploads/';
        $audioSummary = [
            'hasStinger' => file_exists($uploadsDir . 'pod_stinger.mp3'),
            'hasTrailer' => file_exists($uploadsDir . 'pod_trailer.mp3'),
        ];

        $this->viewModel->page->title = 'Settings';
        $this->viewModel->page->tab = 'settings';

        return $this->render('inadmin/page/settings/list.html.twig', [
            'viewModel' => $this->viewModel,
            'activeTheme' => $themeManager->getActiveTheme(),
            'audioSummary' => $audioSummary,
            'discoverySummary' => $discoverySummary,
            'tabs' => $tabManager->getActiveTabs(),
            'version' => $versionService->getAll(),
        ]);
    }
}
