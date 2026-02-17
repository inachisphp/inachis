<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Tools;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\System\Maintenance\MaintenanceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Maintenance controller
 */
#[IsGranted('ROLE_ADMIN')]
class MaintenanceController extends AbstractInachisController
{
    /**
     * Display the maintenance page
     *
     * @param Request $request The request
     * @return Response
     */
    #[Route('/tools/maintenance', name: 'incc_tools_maintenance')]
    public function index(Request $request, MaintenanceManager $manager): Response
    {
        $config = $manager->getConfig();
        $enabled = $manager->isEnabled();
        $currentIp = $request->getClientIp();

        if ($request->isMethod('POST')) {
            $config['message'] = $request->request->get('message', $config['message']);
            $config['estimated_downtime'] = $request->request->get('estimated_downtime', $config['estimated_downtime']);
            $config['allowed_ips'] = array_filter(array_map('trim', explode(',', $request->request->get('allowed_ips', ''))));
            $config['retry_after'] = (int)$request->request->get('retry_after', $config['retry_after'] ?? 3600);

            $manager->saveConfig($config);
            $manager->generateStaticPage($config);

            if ($request->request->get('toggle') === 'on') {
                $manager->enable();
            } elseif ($request->request->get('toggle') === 'off') {
                $manager->disable();
            }

            $this->addFlash('success', 'Maintenance settings updated.');
            return $this->redirectToRoute('incc_tools_maintenance');
        }

        $this->data['page']['title'] = 'Maintenance Mode';
        $this->data['page']['tab'] = 'tools';
        $this->data['config'] = $config;
        $this->data['enabled'] = $enabled;
        $this->data['current_ip'] = $currentIp;
        return $this->render('inadmin/page/tools/maintenance.html.twig', $this->data);
    }
}
