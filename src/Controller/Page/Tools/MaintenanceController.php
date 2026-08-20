<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\Maintenance\MaintenanceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Maintenance controller.
 */
class MaintenanceController extends AbstractInachisController
{
    /**
     * Display the maintenance page.
     *
     * @param Request $request The request
     */
    #[Route('/incp/tools/maintenance', name: 'incp_tools_maintenance')]
    public function index(Request $request, MaintenanceManager $manager): Response
    {
        $config = $manager->getConfig();
        $enabled = $manager->isEnabled();
        $currentIp = $request->getClientIp();

        if ($request->isMethod('POST')) {
            $config['message'] = $request->request->getString('message', $config['message'] ?? '');
            $config['estimated_downtime'] = $request->request->getString('estimated_downtime', $config['estimated_downtime'] ?? '');
            $config['allowed_ips'] = array_filter(array_map('trim', explode(',', $request->request->getString('allowed_ips', ''))));
            $config['retry_after'] = (int) $request->request->getInt('retry_after', $config['retry_after'] ?? 3600);

            $manager->saveConfig($config);
            $manager->generateStaticPage($config);

            if ('on' === $request->request->getString('toggle')) {
                $manager->enable();
            } elseif ('off' === $request->request->getString('toggle')) {
                $manager->disable();
            }

            $this->addFlash('success', 'Maintenance settings updated.');

            return $this->redirectToRoute('incp_tools_maintenance');
        }

        $this->viewModel->page->title = 'Maintenance Mode';
        $this->viewModel->page->tab = 'tools';

        return $this->render('inadmin/page/tools/maintenance.html.twig', [
            'viewModel' => $this->viewModel,
            'enabled' => $enabled,
            'config' => $config,
            'current_ip' => $currentIp,
        ]);
    }

    /**
     * Displays a preview of the maintenance page.
     */
    #[Route('/incp/tools/maintenance/preview', name: 'incp_tools_maintenance_preview')]
    public function preview(MaintenanceManager $manager): Response
    {
        $config = $manager->getConfig();

        return $this->render('web/maintenance_template.html.twig', $config);
    }
}
