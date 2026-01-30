<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\User\UserPreferenceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AppearanceController extends AbstractInachisController
{
    /**
     * Allow a {@link User} to change their preferences (theme, accessibility settings, etc.)
     * 
     * @return Response
     */
    #[Route("/incc/admin/theme", name: "incc_admin_theme", methods: [ "GET", "POST" ])]
    public function edit(Request $request, UserPreferenceProvider $userPreferenceProvider): Response
    {
        $preferences = $userPreferenceProvider->get();

        if ($request->isMethod('POST')) {
            $preferences->setTheme($request->request->get('theme', $preferences->getTheme()));
            $preferences->setFontSize($request->request->get('font_size', $preferences->getFontSize()));
            $preferences->setColor($request->request->get('color', $preferences->getColor()));
            $preferences->setTimezone($request->request->get('timezone', $preferences->getTimezone()));

            $userPreferenceProvider->save($preferences);

            return $this->redirectToRoute('incc_admin_theme'); 
        }

        $this->data['user']['preferences'] = $preferences;
        $this->data['page']['title'] = 'Appearance';
        
        return $this->render('inadmin/page/admin/theme.html.twig', $this->data);
    }
}