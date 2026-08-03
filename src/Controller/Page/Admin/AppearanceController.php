<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Admin;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\UserPreference;
use Inachis\Form\UserPreferenceType;
use Inachis\Service\User\UserPreferenceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Appearance controller
 */
class AppearanceController extends AbstractInachisController
{
    /**
     * Allow a {@link User} to change their preferences (theme, accessibility settings, etc.)
     *
     * @param Request $request
     * @param UserPreferenceProvider $userPreferenceProvider
     * @return Response
     */
    #[Route("/incp/admin/theme", name: "incp_admin_theme", methods: [ "GET", "POST" ])]
    public function edit(Request $request, UserPreferenceProvider $userPreferenceProvider): Response
    {
        /** @var UserPreference */
        $preferences = $userPreferenceProvider->get();
        $form = $this->createForm(UserPreferenceType::class, $preferences);
        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $preferences->setTheme($request->request->getString('theme', $preferences->getTheme()));
            $preferences->setFontSize($request->request->getString('font_size', $preferences->getFontSize()));
            $preferences->setColor($request->request->getString('color', $preferences->getColor()));
            $preferences->setTimezone($request->request->getString('timezone', $preferences->getTimezone()));

            $userPreferenceProvider->save($preferences);

            return $this->redirectToRoute('incp_admin_theme');
        }

        $this->viewModel->page->title = 'Appearance';
        return $this->render('inadmin/page/admin/theme.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'user' => [
                'preferences' => $preferences,
            ],
        ]);
    }
}