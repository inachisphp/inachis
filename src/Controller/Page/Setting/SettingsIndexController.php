<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SettingsIndexController extends AbstractInachisController
{
    #[Route("/incc/settings", name: 'incc_settings_index')]
    public function index(): Response
    {
        $this->data['page']['title'] = 'Settings';
        $this->data['page']['tab'] = 'settings';
        return $this->render('inadmin/page/settings/list.html.twig', $this->data);
    }
}
