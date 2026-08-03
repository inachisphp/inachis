<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Setting;

use Inachis\Controller\AbstractInachisController;
use Inachis\Service\Theme\FeatureRegistry;
use Inachis\Service\Theme\ThemeManager;
use Inachis\Service\Theme\ThemeScanner;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for handling Themes
 */
final class ThemeController extends AbstractInachisController
{
    /**
     * Themes list page
     *
     * @param ThemeScanner $themeScanner
     * @param ThemeManager $themeManager
     * @return Response
     */
    #[Route('/incp/settings/themes', name: 'incp_settings_themes', methods: ['GET'])]
    public function index(ThemeScanner $themeScanner, ThemeManager $themeManager): Response
    {
        $this->viewModel->page->title = 'Themes';
        $this->viewModel->page->tab = 'settings';
        return $this->render('inadmin/page/settings/themes.html.twig', [
            'viewModel' => $this->viewModel,
            'activeTheme' => $themeManager->getActiveTheme(),
            'scanStatus' => $themeScanner->getScanStatus(),
            'themes' => $themeScanner->getThemes(),
        ]);
    }

    /**
     * Rescans the themes folder and redirects back to list
     *
     * @param ThemeScanner $themeScanner
     * @return Response
     */
    #[Route('/incp/settings/themes/rescan',
        name: 'incp_settings_themes_rescan',
        methods: ['GET'])]
    public function rescan(ThemeScanner $themeScanner): Response
    {
        $themeScanner->rescanThemes();
        $this->addFlash('success', 'Theme folders rescanned.');

        return $this->redirectToRoute('incp_settings_themes');
    }

    /**
     * Activates the specified theme
     *
     * @param string $identifier
     * @param ThemeScanner $themeScanner
     * @param ThemeManager $themeManager
     * @param FeatureRegistry $featureRegistry
     * @return Response
     */
    #[Route('/incp/settings/themes/{identifier}/activate',
        name: 'incp_settings_theme_activate',
        methods: ['POST'])]
    public function activate(
        string $identifier,
        ThemeScanner $themeScanner,
        ThemeManager $themeManager,
        FeatureRegistry $featureRegistry,
    ): Response {
        $theme = $themeScanner->getTheme($identifier);

        if (null === $theme) {
            throw new NotFoundHttpException(sprintf('Theme "%s" not found.', $identifier));
        }

        $missingFeatures = array_values(array_filter(
            $theme->requiredFeatures,
            static fn (string $feature): bool => !$featureRegistry->has($feature)
        ));

        if ([] !== $missingFeatures) {
            $this->addFlash(
                'error',
                sprintf(
                    'Cannot activate theme. Missing: %s',
                    implode(', ', $missingFeatures)
                )
            );

            return $this->redirectToRoute('incp_settings_themes');
        }

        $themeManager->setActiveTheme($identifier);
        $this->addFlash('success', sprintf('Theme "%s" activated.', $theme->name));

        return $this->redirectToRoute('incp_settings_themes');
    }

    /**
     * Returns the screenshot for the specified theme based on the identifier provided
     *
     * @param string $identifier
     * @param ThemeScanner $themeScanner
     * @return BinaryFileResponse
     */
    #[Route('/incp/settings/themes/{identifier}/screenshot', name: 'incp_settings_theme_screenshot', methods: ['GET'])]
    public function screenshot(string $identifier, ThemeScanner $themeScanner): Response
    {
        $theme = $themeScanner->getTheme($identifier);
        if (null === $theme || null === $theme->screenshot || !is_file($theme->screenshot)) {
            throw new NotFoundHttpException(sprintf('Screenshot for theme "%s" not found.', $identifier));
        }

        return new BinaryFileResponse($theme->screenshot);
    }
}
