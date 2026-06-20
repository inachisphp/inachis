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
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for handling Themes
 */
#[IsGranted('ROLE_ADMIN')]
final class ThemeController extends AbstractInachisController
{
    /**
     * Themes list page
     *
     * @param ThemeScanner $themeScanner
     * @param ThemeManager $themeManager
     * @return Response
     */
    #[Route('/incc/settings/themes', name: 'incc_settings_themes', methods: ['GET'])]
    public function index(ThemeScanner $themeScanner, ThemeManager $themeManager): Response
    {
        $this->setPageProperties(['title' => 'Themes']);
        $this->data['themes'] = $themeScanner->getThemes();
        $this->data['activeTheme'] = $themeManager->getActiveTheme();
        $this->data['scanStatus'] = $themeScanner->getScanStatus();

        return $this->render('inadmin/page/settings/themes.html.twig', $this->data);
    }

    /**
     * Rescans the themes folder and redirects back to list
     *
     * @param ThemeScanner $themeScanner
     * @return Response
     */
    #[Route('/incc/settings/themes/rescan',
        name: 'incc_settings_themes_rescan',
        methods: ['GET'])]
    public function rescan(ThemeScanner $themeScanner): Response
    {
        $themeScanner->rescanThemes();

        $this->addFlash('success', 'Theme folders rescanned.');

        return $this->redirectToRoute('incc_settings_themes');
    }

    /**
     * Activates the specified theme
     *
     * @param string $slug
     * @param ThemeScanner $themeScanner
     * @param ThemeManager $themeManager
     * @param FeatureRegistry $featureRegistry
     * @return Response
     */
    #[Route('/incc/settings/themes/{slug}/activate',
        name: 'incc_settings_theme_activate',
        methods: ['POST'])]
    public function activate(
        string $slug,
        ThemeScanner $themeScanner,
        ThemeManager $themeManager,
        FeatureRegistry $featureRegistry,
    ): Response {
        $theme = $themeScanner->getTheme($slug);

        if (null === $theme) {
            throw new NotFoundHttpException(sprintf('Theme "%s" not found.', $slug));
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

            return $this->redirectToRoute('incc_settings_themes');
        }

        $themeManager->setActiveTheme($slug);
        $this->addFlash('success', sprintf('Theme "%s" activated.', $theme->name));

        return $this->redirectToRoute('incc_settings_themes');
    }

    /**
     * Returns the screenshot for the specified theme based on the slug provided
     *
     * @param string $slug
     * @param ThemeScanner $themeScanner
     * @return BinaryFileResponse
     */
    #[Route('/incc/settings/themes/{slug}/screenshot', name: 'incc_settings_theme_screenshot', methods: ['GET'])]
    public function screenshot(string $slug, ThemeScanner $themeScanner): Response
    {
        $theme = $themeScanner->getTheme($slug);
        if (null === $theme || null === $theme->screenshot || !is_file($theme->screenshot)) {
            throw new NotFoundHttpException(sprintf('Screenshot for theme "%s" not found.', $slug));
        }

        return new BinaryFileResponse($theme->screenshot);
    }
}
