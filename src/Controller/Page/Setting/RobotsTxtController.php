<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Repository\SettingRepository;
use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for managing the robots.txt content in the admin settings
 */
class RobotsTxtController extends AbstractInachisController
{
	/**
	 * Admin interface to edit the robots.txt content
	 *
	 * @param Request $request
	 * @param SettingRepository $settingRepository
	 * @return Response
	 */
	#[Route('/incc/settings/robots', name: 'incc_settings_robots')]
	public function edit(Request $request, SettingRepository $settingRepository): Response {
        if ($request->isMethod('POST')) {
            $robotsTxt = trim(
                $request->request->get('robots_txt', '')
            );

			if (preg_match('/^Disallow:\s*\/\s*$/mi', $robotsTxt)) {
				$this->addFlash(
					'warning',
					'Your robots.txt blocks the entire site from indexing.'
				);
			}

            $settingRepository->setValue(
                'robots_txt',
                $robotsTxt
            );

            $this->addFlash(
                'success',
                'robots.txt configuration updated.'
            );

            return $this->redirectToRoute(
                'incc_settings_robots'
            );
        }

        $this->data['page']['title'] = 'robots.txt Configuration';
        $this->data['page']['tab'] = 'settings';
		$this->data['robotsTxt'] = $settingRepository->getValue('robots_txt') ?? '';

        return $this->render('/inadmin/page/settings/robots.html.twig', $this->data);
    }
}